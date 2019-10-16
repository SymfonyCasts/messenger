# Exchange Routing and Binding Keys

Let's change this delay back to one second... so we're not waiting all day for our
photos to be processed.

[[[ code('cd12692aea') ]]]

## Simple Setup: 1 Fanout Exchange per Queue

In `messenger.yaml`, the messages sent to each transport - `async` and
`async_priority_high` - need to ultimately be delivered into two different queues
so that we can consume them independently. And... we've accomplished that!

But there are two different ways that we could have done this. First, remember
that in AMQP, messages are sent to an *exchange*, not a queue. Right now, when
a message is routed to the `async` transport, Messenger sends that to an exchange
called `messages`. You don't see that config here only because `messages` is the
default exchange name in Messenger.

When a message is routed to the `async_priority_high` transport, Messenger sends
that to an exchange called `messages_high_priority`. Each transport always sends
to exactly *one* exchange.

Then, each exchange routes *every* message to a single queue, like the `messages`
exchange sends to a `messages` queue... and `messages_high_priority` sends
to a `messages_high` queue. There is *not* a routing key on the binding: Messenger
binds each exchange to *one* queue... but with *no* routing key. That's how a
"fanout" exchange works: it doesn't care about routing keys... it just sends
each message to *every* queue bound to it.

## 1 Direct Exchange to 2 Queues

So that's *one* way to to solve this problem. The *other* way involves having
only a *single* exchange... but making it smart enough to send some messages to
the `messages` queue and other messages to `messages_high`. We do that with smarter
binding and routing keys... which we already saw with the `delays` exchange.

## Configuring a Direct Exchange

Let's refactor our transports to use this "smarter" system. Under the `async`
transport, add `options`, then `exchange`, and set `name` to `messages`. If we
stopped here, this would change *nothing*: this is the default exchange name
in Messenger.

[[[ code('ac1475fe07') ]]]

But now, add a `type` key set to `direct`. This *does* change things: the default
value is `fanout`. Add one more key below this: `default_publish_routing_key`
set to `normal`.

[[[ code('343ffab36d') ]]]

I'll talk about that in a second. Next, add a `queues` section. Let's "bind" this
exchange to a queue called `messages_normal`. But we won't stop there! Under this,
add `binding_keys` set to `[normal]`.

[[[ code('5eb3bee106') ]]]

That word `normal` could be *any* string. But it's no accident that this matches
what we set for `default_publish_routing_key`.

## Deleting all the Exchanges and Queues

Instead of talking a ton about what this will do... let's... see it in action! Click
to delete a photo: that should send a message to the `async` transport. Oh,
but the AJAX call explodes! Open up the profiler to see the error. Ah:

> Server channel error: 406, message: PRECONDITION_FAILED - inequivalent arg
> 'type' for exchange 'messages': received 'direct' but current is 'fanout'

The problem is that we already have an exchange called `messages `, which is
a `fanout` type... but now we're trying to use it as a `direct` exchange. AMQP
is warning us that we're trying to do something crazy!

So let's start over. Now that we're doing things a *new* way, let's hit the
reset button and allow Messenger to create everything new.

Find your terminal - I'll log out of MySQL - and stop your worker... otherwise
it will *keep* trying to create your exchanges and queues with the old config.

Then move back to the RabbitMQ admin, delete the `messages` exchange... then
the `messages_high_priority` exchange. And even though the queues won't look
different, to be extra safe, let's delete both of them too.

So we're back to no queues and only the original exchanges that AMQP created -
which we're not using anyways - and the `delays` exchange. We're starting from
scratch!

Go back to our site, delete the second image and... it looks like it worked!
Cool! Let's see what happened inside RabbitMQ! Yea! We have a new exchange called
`messages` and it's a *direct* type. Inside, it has a *single* binding that
says:

> When a message is sent to this exchange with a routing key called `normal`,
> it will be delivered to the queue called `messages_normal`.

This was *all* set up thanks to the `queues` and `binding_keys` config. This
tells Messenger:

> I want you to create a queue called `messages_normal`. Also, make sure that
> there is a *binding* on the exchange that will route any messages with a
> routing key set to `normal` to this queue.

But... did Messenger *send* the message with that routing key? Until now, other
than the delay stuff, Messenger has been delivering our messages to AMQP with
*no* routing key. The `default_publish_routing_key` config changes that. It
says:

> Hey! Whenever a message is routed to the `async` transport, I want
> you to send it to the `messages` exchange with a routing key set to `normal`.

This *all* means that if we look at the queues... yep! We have a `message_normal`
queue with *one* message waiting inside! We did it!

Next, let's repeat this for the *other* transport. Then, we'll learn how this
gives us the flexibility to dynamically control where a message will be delivered
at the moment we dispatch it.
