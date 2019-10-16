# AMQP Priority Exchange

The idea behind our `async` and `async_priority_high` transports was that we can
send some messages to `async_priority_high` and others to `async`, with the *goal*
that those messages would end up in different "buckets"... or, more technically,
in different "queues". Then we can instruct our worker to *first* read all messages
from whatever queue `async_priority_high` is bound to *before* reading messages
from whatever queue the `async` transport is bound to.

## The queue_name Option in Doctrine

This *did* work before with Doctrine, thanks to this `queue_name: high` option.
The default value for this option is... `default`. As a reminder, I'll quickly
log into my database:

```terminal
mysql -u root messenger_tutorial
```

And see what that table looked like:

```terminal
DESCRIBE messenger_messages;
```

Yep, the `queue_name` column was the key to making this work. Messages that were
sent to `async_priority_high` had a `queue_name` set to `high`, and those sent
to the `async` transport had a value of `default`. So even though we only had
one database table, it functioned like two queues: when we consumed the
`async_priority_high` transport, it queried for all messages
`WHERE queue_name="high"`.

The *problem* is that this `queue_name` option is specific to the *doctrine* transport,
and it has absolutely *no* effect when using AMQP.

## Routing Messages to... a Queue?

But... on a high-level... our goal is the same: we need *two* queues. We need the
`async_priority_high` transport to send messages to *one* queue and the `async`
transport to send messages to a *different* queue.

But with AMQP... you don't send a message directly to a queue... you send it
to an *exchange*... and then it's the exchange's responsibility to look at its
internal rules and figure out which queue, or queues, that message should actually
go to.

This means that to get a message to a queue, we need to tweak things on the
*exchange* level. And there are *two* different ways to do this. First, we could
continue to have a *single* exchange and then add some internal rules - called
*bindings* - to teach the exchange that *some* messages should go to one queue
and *other* messages should go to *another* queue. I'm going to show you how to
do this a bit later.

The second option isn't quite as cool, but it's a bit simpler. By default, when
Messenger creates an exchange, it creates it as a `fanout` type. That means that
when a message is sent to this exchange, it's routed to *every* queue that's bound
to it. So if we added a *second* binding to a second queue - maybe
`messages_high_priority` - then *every* message that's sent to this exchange
would be routed to *both* queues. It would be duplicated! That's... not what
we want.

Instead, we're going to create *two* `fanout` exchanges, and each exchange
will route all of its messages to a *separate* queue. We'll have two exchanges and
two queues.

## Configuring a Second Exchange

Let's configure this inside of `messenger.yaml`. Under `options` add `exchange`
then `name` set to, how about, `messages_high_priority`. Below this, add
`queues` with just one key below: `messages_high` set to `null`.

[[[ code('121cf747af') ]]]

This config has *three* effects. First, because we have the `auto_setup` feature
enabled, the first time we talk to RabbitMQ, Messenger will create the
`messages_high_priority` exchange, the `messages_high` queue *and* bind them together.
The *second* effect is that when we *send* messages to this transport they will
be sent to the `messages_high_priority` exchange. The third and *final* effect
is that when we *consume* from this transport, Messenger will read messages from
the `messages_high` *queue*.

If that still doesn't make complete sense... don't worry: let's see this in
action. First, make sure that your worker is *not* running: our's is stopped.
Now let's go over and delete three photos - one, two, three - and upload
four photos.

Cool! Let's see what happened in RabbitMQ! Inside the manager, click "Exchanges".
Nice! We *do* have a new `messages_high_priority` exchange! The original
`messages` exchange *still* sends all of its messages to a `messages` queue...
but the new exchange sends all of *its* messages to a queue called `messages_high`.
That's thanks to our `queues` config.

And... what's inside each queue? Go check it out! It's *exactly* what we want:
the 3 deleted messages are waiting in the `messages` queue and the 4
newly-uploaded photos are in `messages_high`. Each transport is *successfully*
getting their messages into a separate queue! And *that* means that we can
consume them independently.

At the command line, we would normally tell Messenger to consume from
`async_priority_high` and then `async` to get our prioritized delivery. But
to *clearly* show what's happening, let's consume them independently for now.
Start by consuming messages from the `async` transport:

```terminal-silent
php bin/console messenger:consume -vv async
```

It starts processing the `ImagePostDeletedEvent` objects... and stops after those
three. It's done! That queue is empty. The command did *not* read the messages
from `messages_high`. To do that, consume the `async_priority_high` transport:

```terminal-silent
php bin/console messenger:consume -vv async_priority_high
```

There we go! The *simplest*... but not fanciest... way to have prioritized
transports with AMQP is to send each transport to a different exchange and
configure it to route to a different queue. Later... we'll see the fancier way.

Before we get there, remember when I had you comment-out the `DelayStamp` before
we started using RabbitMQ? Next, I'll show you why: we'll re-add that `DelayStamp`
and see the *crazy* way that messages are "delayed" in RabbitMQ.
