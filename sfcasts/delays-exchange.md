# Delaying in AMQP: Dead Letter Exchange

When we started working with AMQP, I told you to go into `ImagePostController` and
remove the `DelayStamp`. Remember, this stamp is a way to tell the transport system
to *wait* at least 500 milliseconds before allowing a worker to receive the message.
Let's change this to 10 seconds - so `10000` milliseconds.

Then, move over to your terminal and make sure that your worker is *not* running.
currently. Ok, let's go try this! Right now *both* queues are empty. We'll upload
3 photos... then... quick, quick, quick! Go look at the queues. Suddenly, there's
a new one with a strange name `delay_messages_high_priority__10000` with 3 messages
in it. This queue has a *bunch* of features activated on it.

Let's look inside. Interesting, the messages were delivered *here*, instead of
the normal queue. But then... they disappeared? The graph shows how the messages
waiting in this queue went from 3 to 0. But... how? Our worker isn't even running.

Woh! Now this page just 404'ed! The queue is gone! Something is attacking our queues!

Head back to the queue list. Yea, that weird "delay queue *is* gone... oh, but
now the three messages are in `messages_high`. What the heck just happened?

Well first, to prove that the whole system *still* works... regardless of what
craziness just happened... let's run our worker and consume from both the
`async_priority_high` and `async` transports:

```terminal-silent
php bin/console -vv async_priority_high async
```

It consumes them and... then, when we move over, go to the homepage and refresh,
yep! Ponka *was* added to those images.

## The Delay Exchange

Ok, let's figure out what just happened. I mean, on the one hand, it's not important:
if we had been running our worker the entire time, you would have seen that those
messages *were* delayed by 10 seconds. *How* you delay messages in RabbitMQ is
kinda crazy... but if you don't care about the details, Messenger just takes care
of it for you.

I *do* want to see how this works... in part because it will expose us to some
really cool AMQP features.

Click on "Exchanges". Surprise! There's a *new* type of exchange called `delays`.
And instead of `fanout` type like our other two exchanges, this is a `direct` type.
We'll talk more about that that means soon.

But the *first* thing to know is that as soon as a delay is added to your message,
instead of sending it to the "correct" exchange, Messenger will send that messages
to this `delays` exchange. Right now, it has *no* bindings on it... but that will
change when we send a delayed message.

To make this all easier to see, let's temporarily increase the delay to 60 seconds.
We're going to *really* see what happens with the messages now. Ok, upload 3 more
photos. We *now* know that these were just *sent* to the `delays` exchange. And...
check this out, if you refresh, it has a new binding that says:

> If the message has a "routing key" called `delay_messages_high_priority__60000`,
> then I will send the message to a queue that has the same name.

A "routing key" is an extra property that you an set on a message when you're
sending it to AMQP. Normally Messenger doesn't set *any* routing key, but when
a message has a *delay* it *does*. And thanks to this binding - those three messages
are sent to the `delay_messages_high_priority__60000` queue. This is how a `direct`
exchange works: instead of sending each message to *all* queues bound to it, it
uses the "binding key" rules to figure out which queue a message should go to.

## Delay Queues: x-message-ttl and x-deal-letter-exchange

Click into the queue because it's *super* interesting. It has a few important
properties. The first is an `x-message-ttl` set to 60 seconds. What does that means?
When you set this in a queue, it means that, after a message has been sitting in
this queue for 60 seconds, RabbitMQ should remove it... which seems crazy, right?
Why would we want messages to only live for 60 seconds and then be deleted? Well...
it's by design and works together with this second important property:
`x-dead-letter-exchange`.

When a queue has this property, it tells Rabbit that when a message hits its 60
second TTL and needs to be removed, it should *not* be deleted. Instead, it should
be sent to the `messages_high_priority` exchange.

So Messenger delivers messages to the `delays` exchange with a routing key that
makes it get sent here, the message just sits for 60 seconds, then gets delivered
to the right spot. And then... 404! Even the queue itself is marked as "temporary":
once it doesn't have any messages left, it deletes itself.

When you click back to see the Queues, the messages *were* delivered to
the `messages_high` queue... but that's already empty because our worker consumed
them.

So... yea... wow! Whenever we delay a message, Messenger sets *all* of this up:
it creates the temporary delay queue with the TTL and dead letter exchange settings,
adds a binding to the `delays` exchange to route to this queue, then adds the
correct routing key to the message to make sure it ends up in that queue.

You may or may not need them, but you can start to see how *rich* the features
are in AMQP. The most important feature we just saw was the `direct` transport
type: the kind that rely on routing keys to figure out where each message should
go.

Next, could we use direct exchanges for our *non-delayed* message. Instead of
two exchanges that each "fan out" to a different queue, could we create just
*one* exchange that, by using routing keys, delivers the correct messages to
the correct queues? Totally.
