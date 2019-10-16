# Dynamic AMQP Routing Key (AmqpStamp)

Let's repeat the new exchange setup for the `async_priority_high` transport: we
want this to deliver to the *same* direct exchange, but then use a different
routing key to route messages to a different queue.

Change the exchange to `messages`, set the type to `direct`, then use
`default_publish_routing_key` to automatically attach a routing key called `high`
to each message.

Below, for the `messages_high` queue, this tells Messenger that we want this queue
to be created and bound to the exchange. That's cool, but we *now* need that binding
to have a routing key. Set `binding_keys` to `[high]`.

[[[ code('1528a0f27c') ]]]

How can we trigger Messenger to create that new queue and add the new binding? Just
perform *any* operation that uses this transport... like uploading a photo! Ok,
go check out the RabbitMQ manager - start with Exchanges.

Yep, we still have just one `messages` exchange... but now it has two bindings!
If you send a message to this exchange with a `high` routing key, it will be
sent to `message_high`.

Click "Queues" to see... nice - a new `messages_high` queue with one message
waiting inside.

And... we're done! This new setup has the same end-result: each transport ultimately
delivers messages to a different queue. Let's go consume the waiting messages:
consume `async_priority_high` then `async`.

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

And it consumes them in the correct order: handling `AddPonkaToImage` first because
that's in the high priority queue and *then* moving to messages in the other queue.

By the way, when we consume from the `async` transport, for example,
behind-the-scenes, it means that Messenger is *reading* messages from any queue
that's configured for that transport. In our app, each transport has config for
only one queue, but you *could* configure *multiple* queues under a transport and
even set different binding keys for each one. But when you *consume* that transport,
you'll be consuming messages from *every* queue you've configured.

## Dynamic Routing Keys

So, let's back up and look at the whole flow. When we dispatch an `AddPonkaToImage`
object, our Messenger routing config *always* routes this to the `async_priority_high`
transport. This causes the message to be sent to the `messages` exchange with a
routing key set to `high`... and the binding logic means that it will ultimately
be delivered to the `messages_high` queue.

Due to the way that Messenger's routing works - the fact that you route a *class*
to a transport - every message *class* will *always* be delivered to the same
queue. But what if you *did* want to control this dynamically? What if, at the
moment you *dispatch* a message, you needed to send that message to a
*different* transport than normal? Maybe you decide that *this* particular
`AddPonkaToImage` message is *not* important and should be routed to `async`.

Well... that's just *not* possible with Messenger: each class is *always* routed
to a specific transport. But this end-result *is* possible... if you know how
to leverage routing keys.

Here's the trick: what if we could publish an `AddPonkaToImage` object... but tell
Messenger that when it sends it to the exchange, it should use the `normal` routing
key instead of `high`? Yea, the message would *technically* still be routed to
the `async_priority_high` transport... but it would ultimately end up in the
`messages_normal` queue. That would do it!

Is that possible? Totally! Open up `ImagePostController` and find where we dispatch
the message. After the `DelayStamp`, add a new `AmqpStamp` - but be careful not
to choose `AmqpReceivedStamp` - that's something different... and isn't useful
for us. This stamp accepts a few arguments and the first one - gasp! - is the
routing key to use! Pass this `normal`.

[[[ code('d1e8c4baab') ]]]

Let's try it! Stop the worker so we can see what happens internally. Then, upload
a photo, go to the RabbitMQ manager, click on queues... refresh until you see
the message in the right queue... we have to wait for the delay... and there it is!
It ended up in `messages_normal`.

## What else can you Customize on an Amqp Message?

By the way, if you look inside this `AmqpStamp` class, the second and third
arguments are for something called `$flags` and `$attributes`. These are a bit
more advanced, but might just come in handy. I'll hit Shift+Shift to open a
file called `Connection.php` - make sure to open the one in the `AmqpExt`
directory. Now search for a method called `publishOnExchange()`.

When a message is sent to RabbitMQ, *this* is the low-level method that actually
*does* that sending. Those `$flags` and `$attributes` from the stamp are used
here! Passed as the third and fourth arguments to some `$exchange->publish()`
method. Hold Cmd or Ctrl and click to jump into that method.

Oh! This jumps us to a "stub" - a "fake" method & declaration... because this
class - called `AMQPExchange` is *not* something you'll find in your `vendor/`
directory. Nope, this class comes from the AMQP PHP extension that we
installed earlier.

So, if you find that you *really* need to control something about *how* a message
is published through this extension, you can do that with the `$flags` and
`$attributes`. The docs above this do a nice job of showing you the options.

And... that's it for AMQP and RabbitMQ! Sure, there's more to learn about RabbitMQ -
it's a huge topic on its own - but you now have a firm grasp of its most important
concepts and how they work. And unless you need to do some *pretty* advanced stuff,
you understand *plenty* to work with Messenger.

Next, up until now we've been sending messages from our Symfony app *and* consuming
them from that same app. But, that's not always the case. One of the powers of
a "message broker" like RabbitMQ is the ability to send messages from one system
and *handle* them in a totally different system... maybe on a totally different
server or written in a totally different language. Craziness!

But if we're going to use Messenger to *send* messages to a queue that will then
be handled by a totally different app... we probably need to encode those messages
as JSON... instead of the PHP serialized format we're using now.
