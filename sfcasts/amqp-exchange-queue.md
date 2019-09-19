# AMQP Internals: Exchanges & Queues

We've just changed our messenger configuration to send messages to a cloud-based
RabbitMQ instance instead of sending them to Doctrine to be stored in the database.
And after we made that change... everything... just kept working! We can send messages
like normal and consume them with the `messenger:consume` command. That's awesome!

But I want to look a bit more at how this *works*... what's *actually* happening
inside of RabbitMQ. Stop the worker... and then lets go delete a few images: one,
two, three. This should have caused *three* new messages to be sent to Rabbit.

When we were using the Doctrine transport, we could query a database table to see
these. Can we do something similar with RabbitMQ? Yea... we can! RabbitMQ comes
with a *lovely* tool called the RabbitMQ Manager. Click to jump into it.

Aw yea, we've got data! And if we learn what some of these terms mean... this
data will even start to make sense!

## Exchanges

The *first* big concept in RabbitMQ is an *exchange*... and, for me, this was the
most confusing part of learning how Rabbit works. When you send a message to RabbitMQ,
you send it to a specific *exchange*. Most of these exchanges were automatically
created for us... and you can ignore them. But see that `messages` exchange? That
was created by *our* application and, right now, *all* messages that Messenger
transports to RabbitMQ are being sent to *this* exchange.

You won't see the name of this exchange in our messenger config yet, but each
transport that uses AMQP has an `exchange` option and it *defaults* to `messages`.
See this "Type" column? Our exchange is a type called `fanout`. Click into this
exchange to get more info... and open up "bindings". This exchange has a "binding"
to a "queue" that's... by coincidence... *also* called "messages".

## Exchanges Send to Queues

And *this* is where things can get a little confusing... but it's *really* a
simple idea. The two main concepts in RabbitMQ are *exchanges* and *queues*.
We're a lot more familiar with the idea of a queue. When we used the Doctrine
transport type, our database table was basically a queue: it was a big list
of queued messages... and when we ran the worker, it read messages from that
list.

In RabbitMQ, queues have the same role: queues hold messages and we *read*
messages from queues. So then... what the heck do these *exchange* things do?

The *key* difference between the Doctrine transport type and AMQP is that with
AMQP you do *not* send a message directly to a queue. You can't say:

> Hey RabbitMQ! I would like to send this message to the `important_stuff` queue.

Nope, in RabbitMQ, you send messages to an *exchange*. Then, that exchange will
have some config that *routes* that message to a specific queue... or possibly
multiple queues. The "Bindings" represents that config.

The *simplest* type of exchange is this `fanout` type. It says that each
message that's sent to this exchange should be sent to *all* the queues that have
been bound to it... which in our case is just one. The "binding" rules can get a
lot smarter - sending different messages to different queues - but let's worry
about that later. For now, this *whole* fancy setup means that *every* message
will ultimately end up in a queue called `messages`.

Let's click on the Queues link on top. Yep, we have exactly *one* queue: `messages`.
And... hey! It has *3* messages "Ready" inside of it, waiting for us to consume
them!

## auto_setup Exchange & Queues

By the way... who *created* the `messages` exchange and `messages` queue? Are
they... just standard to RabbitMQ? Rabbit *does* come with some exchanges
out-of-the-box, but *these* were created by *our* app. Yep, like with the Doctrine
transport-type, Messenger's AMQP transport has an `auto_setup` option that
defaults to true. This means that it will detect if the exchange and queue it
needs exist, and if they're don't, it will automatically create them. Yep, Messenger
took care of creating the exchange, creating the queue *and* tying them together
with the exchange binding. Both the exchange name *and* queue name are options
that you can configure on your transport... and both default to the word `messages`.
We'll see that config a bit later.

## Send to an Exchange, Read from a Queue

To summarize *all* of this: we *send* a message to an exchange and it forwards it
to one or more queues based on some internal rules. Whoever is "sending" - or
"producing" - the message just says:

> Go to the exchange called "messages"

... and in theory... the "sender" doesn't really know or care what queue that
message will end up in. Once the message *is* in a queue... it just sits there..
and waits!

The second part of the equation is your "worker" - the thing that *consumes*
messages. The worker is the *opposite* of the sender: it doesn't know *anything*
about *exchanges*. It just says:

> Hey! Give me the next message in the "messages" queue.

We send messages to exchanges, RabbitMQ routes those to queues, and we consume
from those queues. The exchange is a new, extra layer... but the end-result is
still pretty simple.

Phew! Before we try to run our worker, let's upload 4 photos. Then.... if you
look at the `messages` queue... and refresh.... there it is! It has 7 messages!

## Consuming from the Queue

As a reminder, we're sending `AddPonkaToImage` messages to `async_priority_high`
and `ImagePostDeletedEvent` to `async`. The idea is that we can put different
messages into different queues and then consume messages in the `async_priority_high`
queue before consuming messages in the `async` queue. The problem is that...
right now... everything is ending up in the *same*, *one* queue!

Check this out - find your terminal and *only* consume from the `async` transport.
This *should* cause *only* the `ImagePostDeletedEvent` messages to be consumed:

```terminal-silent
php bin/console messenger:consume -vv async
```

And... yup, it does handle a few `ImagePostDeletedEvent` objects. But if you keep
watching... once it finishes those, it *does* start processing the `AddPonkaToImage`
messages.

We have *such* a simple AMQP setup right now that we've introduced a bug: our
two transports are *actually* sending to the exact same queue... which kills
our ability to consume them in a prioritized way. We'll fix that next by using
*two* exchanges.

Oh, but if you flip back over to the RabbitMQ manager - you can see all the
messages being consumed. Cool stuff.
