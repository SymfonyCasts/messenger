# Delaying in AMQP: Dead Letter Exchange

When we started working with AMQP, I told you to go into `ImagePostController` and
remove the `DelayStamp`. This stamp is a way to tell the transport system to
*wait* at least 500 milliseconds before allowing a worker to receive the message.
Let's change this to 10 seconds - so `10000` milliseconds.

[[[ code('0eff211920') ]]]

Now, move over to your terminal and make sure that your worker is *not* running.

Ok, let's see what happens! Right now *both* queues are empty. I'll upload
3 photos... then... quick, quick, quick! Go look at the queues. Suddenly, poof!
A new queue appeared... with a strange name: `delay_messages_high_priority__10000`.
And it has - dun, dun, dun! - *three* messages in it.

Let's look inside. Interesting, the messages were delivered *here*, instead of
the normal queue. But then... they disappeared? The graph shows how the messages
sitting in this queue went from 3 to 0. But... how? Our worker isn't even running!

Woh! This page just 404'ed! The queue is gone! Something is attacking our queues!

Head back to the queue list. Yea, that weird "delay" queue *is* gone... oh, but
now the three messages are somehow in `messages_high`. What the heck just happened?

Well first, to prove that the whole system *still* works... regardless of what
craziness just occurred... let's run our worker and consume from both the
`async_priority_high` and `async` transports:

```terminal-silent
php bin/console -vv async_priority_high async
```

It consumes them and... when we move over, go to the homepage and refresh, yep!
Ponka *was* added to those images.

## The Delay Exchange

Ok, let's figure out how this worked. I mean, on the one hand, it's not important:
if we had been running our worker the entire time, you would have seen that those
messages *were* in fact delayed by 10 seconds. *How* you delay messages in RabbitMQ
is kinda crazy... but if you don't care about the details, Messenger just takes
care of it for you.

But I *do* want to see how this works... in part because it'll be a *great* chance
to see how some of the more advanced features of AMQP work.

Click on "Exchanges". Surprise! There's a *new* exchange called `delays`. And
instead of being a `fanout` type like our other two exchanges, this is a `direct`
exchange. We'll talk about what that that means soon.

But the *first* thing to know is that when Messenger sees that a message should
be delayed, it sends it to *this* exchange *instead* of sending it to the normal,
"correct" exchange. At this moment, the `delays` exchange has *no* bindings...
but that will change when we send a delayed message.

To be able to *really* see what's happening, let's increase the delay to 60 seconds.

[[[ code('465e79bf29') ]]]

Ok, upload 3 more photos: we *now* know that these were just *sent* to the `delays`
exchange. And... if you refresh that exchange... it has a new binding! This says:

> If a message sent here has a "routing key" set to
> `delay_messages_high_priority__60000`, then I will send that message to a
> queue called delay_messages_high_priority__60000

A "routing key" is an extra property that you can set on a message that's sent
to AMQP. Normally Messenger doesn't set *any* routing key, but when a message
has a *delay*, it *does*. And thanks to this binding - those three messages
are sent to the `delay_messages_high_priority__60000` queue. This is how a `direct`
exchange works: instead of sending each message to *all* queues bound to it, it
uses the "binding key" rules to figure out which queue - or *queues* - a message
should go to.

## Delay Queues: x-message-ttl and x-deal-letter-exchange

Click into the queue because it's *super* interesting. It has a few important
properties. The first is an `x-message-ttl` set to 60 seconds. What does that means?
When you set this on a queue, it means that, after a message has been sitting in
this queue for 60 seconds, RabbitMQ should remove it... which seems crazy, right?
Why would we want messages to only live for 60 seconds... and then be deleted? Well...
it's by design... and works together with this second important property:
`x-dead-letter-exchange`.

If a queue has this property, it tells Rabbit that when a message hits its 60
second TTL and needs to be removed, it should *not* be deleted. Instead, it should
be *sent* to the `messages_high_priority` exchange.

So, Messenger delivers messages to the `delays` exchange with a routing key that
makes it get sent here. Then, after sitting around for 60 seconds, the message
is removed from this queue and sent to the `messages_high_priority` exchange.
Yep, it's delivered to the correct place after 60 seconds!

And then... 404! Even the queue itself is marked as "temporary": once it doesn't
have any messages left, it deletes itself.

When you click back to see the Queues, the messages *were* delivered to
the `messages_high` queue... but that's already empty because our worker consumed
them.

So... yea... wow! Whenever we publish a message with a delay, Messenger sets *all*
of this up: it creates the temporary delay queue with the TTL and dead letter
exchange settings, adds a binding to the `delays` exchange to route to this queue,
and adds the correct routing key to the message to make sure it ends up in that queue.

You can *really* start to see how *rich* the features are in AMQP... even if you
won't need them. The most important feature we just saw was the `direct` exchange
type: an exchange that relies on routing keys to figure out where each message
should go.

Next, could we use direct exchanges for our *non-delayed* messages? Instead of
two exchanges that each "fan out" to a separate queue, could we create just
*one* exchange that, by using routing keys, delivers the correct messages to
the correct queues? Totally.
