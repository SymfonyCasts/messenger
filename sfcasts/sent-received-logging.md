# Middleware Message Lifecycle Logging

Our middleware is called in *two* different situations. First, it's
called when you initially dispatch the message. For example, in
`ImagePostController`, the moment we call `$messageBus->dispatch()`, all the
middleware are called - regardless of whether or not the message will be handled
async. And second, when the worker - `bin/console messenger:consume` - receives
a message from the transport, it passes that message *back* into the bus and
the middleware are called again.

This is the trickiest thing about middleware: trying to figure out which situation
you're currently in. Fortunately, Messenger adds "stamps" to the `Envelope` along
the way, and *these* tell us *exactly* what's going on.

## Was the Message Received from the Transport? ReceivedStamp

For example, when a message is *received* from a transport, messenger adds a
`ReceivedStamp`. So, if `$envelope->last(ReceivedStamp::class)`, then this message
is currently being processed by the worker and was just received from a transport.

[[[ code('209bc336b0') ]]]

Let's log that: `$this->logger->info()` with a special syntax:

> [{id}] Received and handling {class}

Then pass `$context` as the second argument. The `$context` array is cool for two
reasons. First, each log handler receives this and can do whatever it wants with
it - usually the `$context` is printed at the end of the log message. And second,
if you use these little `{}` wildcards, the context values will get filled in automatically!

[[[ code('12a5f84b02') ]]]

If the message was *not* just received, say `$this->logger->info()` and start the
same way:

> [{id}] Handling or sending {class}

[[[ code('2c51728cbd') ]]]

At this point, we know that the message was *just* dispatched... but we don't
know whether or not it will be handled right now or sent to a transport. We'll
improve that in a few minutes.

But first, let's try it! Start the worker and tell it to read from the `async`
transport:

```terminal-silent
php bin/console messenger:consume -vv async
```

Ah, I think we had a few messages from earlier still in the queue! When that finishes,
let's clear the screen. Let's also open up *another* tab and create the new log
file - `messenger.log` - if it's not already there:

```terminal
touch var/log/messenger.log
```

Then, tail it so we can watch the messages:

```terminal
tail -f var/log/messenger.log
```

Oh, cool! This already has a few lines from those old messages it just processed.
Let's clear that so we have fresh screens to look at.

Testing time! Move over and upload one new photo. Spin back to your terminal and...
yea! Both log messages are already there: "Handling or sending" and then
"Received and handling" when the message was received from the transport... which
was almost instant. We know these log entries are for the *same* message thanks
to the unique id at the beginning.

## Determining if Message is Handled or Sent

But... we can do better than just saying "handling *or* sending". How? This
`$stack->next()->handle()` line is responsible for calling the *next* middleware...
which will then call the *next* middleware and so on. Because our logging code is
*above* this, it means that our code is potentially being called *before* some
other middleware do their work. In fact, our code is being executed before the
core middleware that are responsible for handling or sending the message.

So... how can we determine whether the message will be sent versus handled
immediately... before the message is *actually* sent or handled immediately?
We can't!

Check it out: remove the `return` and instead say
`$envelope = $stack->next()->handle()`. Then, move that line *above* our code and,
at the bottom, `return $envelope`.

[[[ code('e1632c7ff0') ]]]

If we did *nothing* else... the result would be pretty much the same: we would
log the *exact* same messages... but technically, the log entries would happen
*after* the message was sent or handled instead of before.

*But*! Notice that when we call `$stack->next()->handle()` to execute the rest of
the middleware, we get back an `$envelope`... which *may* contain new stamps! In
fact, *if* the message was sent to a transport instead of being handled immediately,
it will be marked with a `SentStamp`.

Add `elseif` `$envelope->last(SentStamp::class)` then we know that this
message was *sent*, *not* handled. Use `$this->logger->info()` with our `{id}`
trick and `sent {class}`.

[[[ code('38cabed984') ]]]

Below, now we know that we're definitely "Handling sync". The top message -
"Received and handling" is still true, but I'll change this to just say "Received":
a message is *always* handled when it's received, so that was redundant.

[[[ code('3f3fe5054b') ]]]

Ok! Let's clear our log screen and restart the worker:

```terminal-silent
php bin/console messenger:consume -vv async
```

Upload one photo... then move over... and go to the log file. Yep! Sent,
then Received! If we had uploaded 5 photos, we could use the unique id to identify
each message individually.

Hit enter a few times: I want to see an even *cooler* example. Delete a photo and
move back over! Remember, this dispatches *two* messages! The unique id part makes
it even *more* obvious what's going on: `DeletePhotoFile` was sent to the
transport, then `DeleteImagePost` was handled synchronously... then
`DeletePhotoFile` was received and processed.

Actually, what *really* happened was this: `DeleteImagePost` was handled
synchronously and, internally, it dispatched `DeletePhotoFile` which was sent to
the transport. The first two messages are a bit out of order because our logging
code is always running *after* we execute the rest of the chain, so *after*
`DeleteImagePost` was handled. We could improve that by moving the
`Handling Sync` logging logic *above* the code that calls the rest of the
middleware. Yea, this stuff is *super* powerful... but can be a bit complex
to navigate. This logging stuff is probably as confusing as it gets.

Next: the worker handles each message in the order it was received. But... that's
not ideal: it's *way* more important for *all* `AddPonkaToImage` messages to be
handled before *any* `DeletePhotoFile` messages. Let's do that with
priority transports.
