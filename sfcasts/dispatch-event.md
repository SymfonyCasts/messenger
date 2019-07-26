# Dispatching the Event & ... the EventDispatcher?

Back in `DeleteImagePostHandler`, we need to dispatch our new
`ImagePostDeletedEventMessage`. Earlier, we created a *second* message bus service:
a bus that we're using as a command bus called `messenger.bus.default` and another
one called `event.bus`. Thanks to this, when we run:

```terminal-silent
php bin/console debug:autowiring mess
```

we can now autowire *either* of these services. *Just* using the `MessageBusInterface`
type-hint will give us the main command bus. But using that type-hint *plus* naming
the argument `$eventBus` will give us the other.

Inside `DeleteImagePostHandler`, change the argument to `$eventBus`. I don't have
to, but I'm also going to rename the property to `$eventBus` for clarity. Oh, and
variables need a `$` in PHP. Perfect!

Inside `__invoke()`, it's really the same as before: `$this->eventBus->dispatch()`
with `new ImagePostDeletedEvent()` passing that `$filename`.

That's it! All the work... to do the *same* thing as before, but with some renaming
to match the "event bus" pattern. The handler performs its *primary* task - deleting
the record from the database - then dispatches an event that says:

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
`App\Messages\Event\ImagePostDeletedEvent` to be `async`.

Let's try this! Find your worker and restart it. All of this refactoring was around
deleting images so... let's delete a couple of things, move back over and... yea!
It's working great! `ImagePostDeletedEvent` is being dispatched and handled.

Oh, and side note abour routing. When you route a command class, you know *exactly*
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
you have to. We won't talk about *how* in this tutorial, but it's in the docs.

## Events can have No Handlers

Anyways, I mentioned before that, for events, it's legal on a philosophical level
to have *no* handlers... though you probably won't do that in your application
because... what would the point of creating the event be? But... for the sake
of trying it, open `RemoveFileWhenImagePostDeleted` and delete the
`implements MessageHandleInterface` part.

I'm doing this temporarily to see what happens if Symfony sees *zero* handlers
for an event. Let's... find out! Back in the browser, try to delete an image.
It works! Wait... oh, I forgot to stop the worker... let's do that... then try
again. This time... it works... but in the worker log... CRITICAL error!

> Exception occurred while handling `ImagePostDeletedEvent`: no handler for message.

By default, Messenger *requires* each message to have at *least* one handler. That's
to help you avoid silly mistakes. But... for an event bus... we *do* want to allow
*zero* listeners. Again... this is more of a philosophical problem than a real one:
it's unlikely you'll decide to dispatch events that have no handlers. But, let's
see how to fix it!

In `messenger.yaml`, take the `~` off of `event.bus` and add a new option below:
`default_middleware: allow_no_handlers`. The `default_middleware` defaults to
`true` and its *main* purpose is to allow you to set it to `false` if, for some
reason, you wanted to *completely* remove the default middleware - the middleware
that handle & send the messages, among other things. But you can also set it to
`allow_no_handlers` if you want to *keep* the normal middleware, but *hint* to
the `HandleMessageMiddleware` that it should *no* panic if there are zero handlers.

Go back and restart the worker. Then, delete another image... come back here and...
cool! It says "No handler for message" but it doesn't freak out and cause a failure.

So now our command bus and event bus *do* have a small difference... though they're
still *almost* identical... and we could *really* still get away with sending both
commands and events through the same bus. Put the `MessageHandlerInterface` back
on the class... and restart our worker one more time.

## Messenger Events vs EventDispatcher?

Now that we're feeling good about events... I have a question: what's the difference
between dispatching an event into Messenger versus dispatching an event into
Symfony's EventDispatcher?

If you've ever create an event listener or event subscriber in Symfony, you're
creating a "listener" for an event that's dispatched through a service called
the "event dispatcher". The purpose of the event dispatcher is to allow one piece
of code to "notify" that something happened and for anyone else to "listen" to
that event and run some code.

Which... huh... is the *exact* same purpose of dispatching an event into Messenger.
What he heck? If I want to dispatch an event in my code, should I use the
EventDispatcher or Messenger? Are image files pronounced as "jif" or "gif"?
Should toilet paper hang "over" the roll or "under"? Ahh!

First, there *is* a practical difference between dispatching an event to the
EventDispatcher versus Messenger: Messenger allows your handlers to be handled
*asynchronously*, whereas listeners to events from the EventDispatcher are
*always* synchronous.

And this leads to a nice rule of thumb. Whenever you dispatch an event, *if* you
want listeners to that event to be able to communicate *back* to you, so you
can then do something based on that feedback, use the EventDispatcher. But if
you simply want to say "this thing happened" and you don't need any feedback
from possible listeners or handlers, use Messenger.

For example, in `AddPonkaToImageHandler`, suppose we wanted to dispatch an event
here so that other parts of the system could tell us exactly *which* Ponka image
should be added to this image. In that case, we need those listeners to be able
to communicate *back* to us. To do that we would create an Event class that holds
the `ImagePost` object *and* has a setter on it that listeners can call - maybe
`setPonkaImageToUse()`. We would then use the `EventDispatcher` and dispatch the
message *before* actually adding Ponka to the image.

But what if we simply wanted to say:

> Hey! We just added Ponka to an image!

... add didn't need any information back from possible handlers? In that case we
would create a similar event class, leave *off* the `setPonkaImageToUse()` method
and dispatch it with Messenger. Messenger is perfect if you don't need any info
back from your handlers because... those handlers might end up being called
asynchronously!

If it's *still* not clear to you, the just use whichever you want. Why? Because
if you end up wanting your code to run asynchronously, you'll end up choosing
Messenger. And if you want your listeners to be able to communicate back to your
code that dispatches the messages, you'll use EventDispatcher. Otherwise, either
will work.

Next, let's use some service configuration tricks to tighten up how we've organized
our commands and command handlers versus our events and event handlers.
