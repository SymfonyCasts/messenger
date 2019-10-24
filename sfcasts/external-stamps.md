# The Lifecycle of a Message & its Stamps

Forget about asynchronous messages and external transports and all that stuff.
Open up `ImagePostController`. As a reminder, when you dispatch a message, you
*actually* dispatch an `Envelope` object, which is a simple "wrapper" that contains
the message itself and *may* also contain some stamps... which add extra info.

If you dispatch the *message* object directly, the message bus creates an
`Envelope` *for* you and puts your message inside. The point is, internally, Messenger
is *always* working with an `Envelope`. And when you call `$messageBus->dispatch()`,
it also *returns* an `Envelope`: the *final* `Envelope` after Messenger has
done all its work.

Let's see what that looks like: `dump()` that whole `$messageBus->dispatch()` line.
Now, move over and upload a photo. Once that's done, find that request on the
web debug toolbar... and open the profiler.

[[[ code('58d6cd358b') ]]]

## The Envelope & Stamps after Dispatching

Perfect! You can see that the *final* `Envelope` has the original message object
inside: `AddPonkaToImage`. But this `Envelope` *now* has more *stamps* on it.

Quick review time! When we dispatch a message into the message bus, it goes through
a collection of *middleware*... and each middleware can add extra *stamps* to the
envelope. If you expand `stamps` in the dump, wow! There are now *5* stamps! The
first two - `DelayStamp` and `AmqpStamp` - are no mystery. *We* added those
manually when we originally dispatched the message. The *last* one - `SentStamp` -
is a stamp that's added by the `SendMessageMiddleware`. Because we've configured
this message to be routed to the `async_priority_high` transport, the
`SendMessageMiddleware` *sends* the message to RabbitMQ and then adds this `SentStamp`.
This is a *signal* - to anyone who cares - us, or other middleware - that this
message *was* in fact "sent" to a transport. Actually, it's *thanks* to this
stamp that the *next* middleware that executes - `HandleMessageMiddleware` - knows
that it should *not* handle this message right now. It sees that `SentStamp`,
realizes the message was sent to a transport and so, does nothing. It will be
handled later.

## BusNameStamp: How the Worker Dispatches to the Correct Bus

But what about this `BusNameStamp`? Let's open up that class. Huh, `BusNameStamp`
*literally* contains... the name of the bus that the message was dispatched into.
If you look in `messenger.yaml`, at the top, we have *three* buses:
`command.bus`, `event.bus` and `query.bus`. Ok, but what's the point of
`BusNameStamp`? I mean, we *dispatched* the message through the command bus...
so why is it important that the message has a stamp on it that says this?

The answer is all about what happens when a worker *consumes* this message. The
process looks like this. First, the `messenger:consume` command - that's the "worker" -
reads a message off of a queue. Second, that transport's serializer turns that into
an `Envelope` object with a message object inside - like our `LogEmoji` object.
Finally, the worker *dispatches* that Envelope back into the message bus!
Yea, internally, something calls `$messageBus->dispatch($envelope)`!

Wait... but if we have *multiple* message buses... how does the worker know
*which* message bus it should dispatch the Envelope into? Whelp! *That* is
the *purpose* of this `BusNameStamp`. Messenger adds this stamp so that when the
worker *receives* this message, it can use the stamp to dispatch the message into
the correct bus.

Right now, in our serializer, we're not adding *any* stamps to the `Envelope`.
Because the stamp doesn't exist, the worker uses the `default_bus`, which is
the `command.bus`. So, in this case... it guessed correctly! This message *is*
a command.

## The UniqueIdStamp

The *last* stamp that was added was this `UniqueIdStamp`. This is something
that *we* created... and it's added via a custom middleware: `AuditMiddleware`.
Whenever a message is dispatched, this middleware makes sure that every `Envelope`
has exactly *one* `UniqueIdStamp`. Then, anyone can use the unique id string on
that stamp to track *this* exact message through the whole process.

Wait... so if this is *normally* added when we originally *dispatch* a message...
should we manually add the stamp inside of our serializer so that the `Envelope`
has one?

Look at it this way: a *normal* message that's *sent* from our app would *already*
have this stamp by the time it's published to RabbitMQ. When a worker receives
it, it'll be there.

But... in this case, as you can clearly see, after receiving the external message,
we are *not* adding that stamp. So, is that something we should add here so this
"acts" like other messages?

Great question! The answer is... no! Check out the log messages: you can already
see some messages with this `5d7bc` string. *That* is the unique id. Our message
*does* have a `UniqueIdStamp`!

How? Remember, after our serializer returns the `Envelope`, the worker dispatches
it *back* through the bus. And so, our `AuditMiddleware` is called, it adds that
stamp and then logs some messages about it.

## The Big Takeaways

To back up a bit, there are *two* big points I'm trying to make. First, when a
message is read and handled via a worker, it is *dispatched* through the message
bus and all the normal middleware are executed. For a message that is both sent
from our app *and* handled by our app, it will go through the middleware *two* times.

The second important point is that when you consume a message that was put there
from an external system, that message *might* be missing some stamps that a normal
message would have. And, for the most part, that's probably fine! The `DelayStamp`
and `AmqpStamp` are irrelevant because those both tell the transport how to
*send* the message.

## Adding the BusNameStamp

But... the `BusNameStamp` *is* one that you might want to add. Sure, Messenger
used the correct bus in this case by accident, but we can be more explicit!

Head into `ExternalJsonMessengerSerializer`. Change this to
`$envelope = new Envelope()` and, at the bottom, return `$envelope`. Add the stamp
with `$envelope = $envelope->with()` - this is how you add a stamp -
`new BusNameStamp()`.

Then... hmm... because our transport & serializer only handle this *one* message...
and because this *one* message is a command, we'll want to put the command bus
here. Copy the `command.bus` bus name and paste. I'll add a comment that says that
this is *technically* only needed if you need the message to be sent through a
non-default bus.

[[[ code('00dceb24cd') ]]]

Next, our serializer is great, but we didn't code very defensively. What would
happen if the message contained invalid JSON... or was missing the `emoji` field?
Would our app fail gracefully... or explode?
