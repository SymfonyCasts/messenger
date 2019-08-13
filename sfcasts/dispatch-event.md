# Dispatching the Event & No Handlers

Back in `DeleteImagePostHandler`, we need to dispatch our new
`ImagePostDeletedEvent` message. Earlier, we created a *second* message bus service.
We now have a bus that we're using as a command bus called `messenger.bus.default`
and another one called `event.bus`. Thanks to this, when we run:

```terminal
php bin/console debug:autowiring mess
```

we can now autowire *either* of these services. *Just* using the `MessageBusInterface`
type-hint will give us the main command bus. But using that type-hint *plus* naming
the argument `$eventBus` will give us the other.

Inside `DeleteImagePostHandler`, change the argument to `$eventBus`. I don't have
to, but I'm also going to rename the property to `$eventBus` for clarity. Oh, and
variables need a `$` in PHP. Perfect!

[[[ code('303f4b730d') ]]]

Inside `__invoke()`, it's really the same as before: `$this->eventBus->dispatch()`
with `new ImagePostDeletedEvent()` passing that `$filename`.

[[[ code('910a58c952') ]]]

That's it! The end result of all of this work... was to do the *same* thing as
before, but with some renaming to match the "event bus" pattern. The handler
performs its *primary* task - deleting the record from the database - then
dispatches an event that says:

> An image post was just deleted! If anyone cares... do something!

## Routing Events

In fact, unlike with commands, when we dispatch an event... we don't actually care
if there are *any* handlers for it. There could be zero, 5, 10 - we don't care!
We're not going to use any return values from the handlers and, unlike with commands,
we're not going to *expect* that *anything* specific happened. You're just screaming
out into space:

> Hey! An ImagePost was deleted!

Anyways, the last piece we need to fix to make this *truly* identical to before
is, in `config/packages/messenger.yaml`, down under `routing`, route
`App\Message\Event\ImagePostDeletedEvent` to the `async` transport.

[[[ code('dc0a84cae3') ]]]

Let's try this! Find your worker and restart it. All of this refactoring was around
deleting images so... let's delete a couple of things, move back over and... yea!
It's working great! `ImagePostDeletedEvent` is being dispatched and handled.

## Handle Some Handlers Async?

Oh, and side note about routing. When you route a command class, you know *exactly*
which *one* handler it has. And so, it's super easy to think about what that handler
does and determine whether or not it can be handled async.

With events, it's a bit more complicated: this *one* event class could have
*multiple* handlers. And, in theory, you might want some to be handled immediately
and *others* later. Because Messenger is built around routing the *messages* to
transports - not the handlers - making some handlers sync and others async isn't
natural. However, if you need to do this, it *is* possible: you can route a message
to *multiple* transports, then configure Messenger to only call *one* handler
when it's received from transport A and only the *other* handler when it's received
from transport B. It's a bit more complex, so I don't recommend doing this unless
you need to. We won't talk about *how* in this tutorial, but it's in the docs.

## Events can have No Handlers

Anyways, I mentioned before that, for events, it's legal on a philosophical level
to have *no* handlers... though you probably won't do that in your application
because... what's the point of dispatching an event with no handlers? But... for
the sake of trying it, open `RemoveFileWhenImagePostDeleted` and take off the
`implements MessageHandleInterface` part.

[[[ code('298d329ef1') ]]]

I'm doing this temporarily to see what happens if Symfony sees *zero* handlers
for an event. Let's... find out! Back in the browser, try to delete an image.
It works! Wait... oh, I forgot to stop the worker... let's do that... then try
again. This time... it works... but in the worker log... CRITICAL error!

> Exception occurred while handling `ImagePostDeletedEvent`: no handler for message.

By default, Messenger *requires* each message to have at *least* one handler. That's
to help us avoid silly mistakes. But... for an event bus... we *do* want to allow
*zero* handlers. Again... this is more of a philosophical problem than a real one:
it's unlikely you'll decide to dispatch events that have no handlers. But, let's
see how to fix it!

In `messenger.yaml`, take the `~` off of `event.bus` and add a new option below:
`default_middleware: allow_no_handlers`. The `default_middleware` option defaults
to `true` and its *main* purpose is to allow you to set it to `false` if, for some
reason, you wanted to *completely* remove the default middleware - the middleware
that handle & send the messages, among other things. But you can also set it to
`allow_no_handlers` if you want to *keep* the normal middleware, but *hint* to
the `HandleMessageMiddleware` that it should *not* panic if there are zero handlers.

[[[ code('96db3c2c63') ]]]

Go back and restart the worker. Then, delete another image... come back here and...
cool! It says "No handler for message" but it doesn't freak out and cause a failure.

So now our command bus and event bus *do* have a small difference... though they're
still *almost* identical... and we could *really* still get away with sending both
commands and events through the same bus. Put the `MessageHandlerInterface` back
on the class... and restart our worker one more time.

[[[ code('f5c0278b7b') ]]]

Now that we're feeling good about events... I have a question: what's the difference
between dispatching an event into Messenger versus dispatching an event into
Symfony's EventDispatcher?

Let's talk about that next.
