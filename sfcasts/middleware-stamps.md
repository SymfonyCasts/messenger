# Tracking Messages with Middleware & a Stamp

We somehow want to attach a unique id - just some string - that stays with the
message forever: whether it's handled immediately, sent to a transport, or even
retried multiple times.

## Creating a Stamp

How can we attach extra... "stuff" to a message? By giving it our very-own stamp!
In the `Messenger/` directory, create a new PHP class called `UniqueIdStamp`. Stamps
*also* have just one rule: they implement
`MessengerEnvelopeMetadataAwareContainerReaderInterface`. Nah I'm kidding - that
would be a silly name. They just need to implement `StampInterface`.

[[[ code('3e253f2210') ]]]

And... that's it! This is an empty interface that just serves to "mark" objects
as stamps. Inside... we get to do *whatever* we want... as long as PHP can serialize
this message... which basically means: as long as it holds simple data. Let's add
a `private $uniqueId` property, then a constructor with no arguments. Inside, say
`$this->uniqueId = uniqid()`. At the bottom, go to Code -> Generate - or Command+N
on a Mac - and generate the getter... which will return a `string`.

[[[ code('cfcc1c6ab8') ]]]

Stamp, done!

## Stamping... um... Attaching the Stamp

Next, inside `AuditMiddleware`, *before* we call the next middleware - which will
call the rest of the middleware and ultimately handle or send the message - let's
add the stamp.

But, be careful: we need to make sure that we only attach the stamp *once*. As we'll
see in a minute, a message may be passed to the bus - and so, to the middleware -
*many* times! Once when it's initially dispatched and *again* when it's received
from the transport and handled. If handling that message fails and is retried, it
would go through the bus even *more* times.

So, start by checking if `null === $envelope->last(UniqueIdStamp::class)`, then
`$envelope = $envelope->with(new UniqueIdStamp())`.

[[[ code('36818753c4') ]]]

## Envelopes are Immutable

There are a few interesting things here. First, each `Envelope` is "immutable",
which means that, just due to the way that class was written, you can't change any
data on it. When you call `$envelope->with()` to *add* a new stamp, it doesn't
*actually* modify the `Envelope`. Nope, internally, it makes a clone of itself *plus*
the new stamp.

That's... not very important *except* that you need to remember to say
`$envelope = $envelope->with()` so that `$envelope` becomes the newly stamped object.

## Fetching Stamps

Also, when it comes to stamps, an `Envelope` could, in theory, hold *multiple*
stamps of the same class. The `$envelope->last()` method says:

> Give me the most recently added `UniqueIdStamp` or null if there are none.

## Dumping the Unique Id

Thanks to our work, below the if statement - regardless of whether this message
was *just* dispatched... or just received from a transport... or is being retried -
our `Envelope` should have exactly *one* `UniqueIdStamp`. Fetch it off with
`$stamp = $envelope->last(UniqueIdStamp::class)`. I'm also going to add a little
hint to my editor so that it knows that this is specifically a `UniqueIdStamp`.

[[[ code('7de893d64e') ]]]

To see if this is working, let's `dump($stamp->getUniqueId())`.

[[[ code('b3069963f6') ]]]

Let's try it! If we've done our job well, for an asynchronous message, that `dump()`
will be executed once when the message is dispatched and *again* inside of the
worker when it's received from the transport and handled.

Refresh the page just to be safe, then upload an image. To see if our `dump()` was
hit, I'll use the link on the web debug toolbar to open up the profiler for that
request. Click "Debug" on the left and... there it is! Our unique id! In a
few minutes, we'll make sure that this code is *also* executed in the worker.

And because middleware are executed for *every* message, we should also be able to
see this when *deleting* a message. Click that, then open up the profiler for the
DELETE request and click "Debug". Ha! This time there are *two* distinct unique
ids because deleting dispatches *two* different messages.

Next, let's actually do something useful with this! Inside of our middleware, we're
going to log the *entire* lifecycle of a single message: when it's originally
dispatched, when it's sent to a transport and when it's received from a transport
and handled. To figure out which part of the process the message is currently in,
we're going to once again use stamps. But instead of creating *new* stamps, we'll
read the *core* stamps.
