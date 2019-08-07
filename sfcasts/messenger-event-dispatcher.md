# Messenger vs EventDispatcher

If you've ever create an event listener or event subscriber in Symfony, you're
creating a "listener" for an event that's dispatched through a service called
the "event dispatcher". The purpose of the event dispatcher is to allow one piece
of code to "notify" the app that something happened and for anyone else to "listen"
to that event and run some code.

Which... huh... is the *exact* same purpose of dispatching an event into Messenger.
What the heck? If I want to dispatch an event in my code, should I use the
EventDispatcher or Messenger? Are animated image files pronounced "jif" or "gif"?
Should toilet paper hang "over" the roll or "under"? Ahh!

## Messenger can be Async

First, there *is* a practical difference between dispatching an event to the
EventDispatcher versus Messenger: Messenger allows your handlers to be called
*asynchronously*, whereas listeners to events from the EventDispatcher are
*always* synchronous.

## EventDispatcher communicates back

And this leads to a nice rule of thumb. Whenever you dispatch an event, *if* you
want listeners to that event to be able to communicate *back* to you, so you
can then do something based on their feedback, use the EventDispatcher. But if
you simply want to say "this thing happened" and you don't need any feedback
from possible listeners or handlers, use Messenger.

For example, in `AddPonkaToImageHandler`, suppose we wanted to dispatch an event
here so that other parts of the system could tell us exactly *which* Ponka image
should be added to this photo. In that case, we need those listeners to be able
to communicate *back* to us. To do that we would create an Event class that holds
the `ImagePost` object *and* has a setter on it that listeners can call - maybe
`setPonkaImageToUse()`. We would then use the `EventDispatcher` and dispatch the
message *before* actually adding Ponka to the image. Once all the listeners were
called, we could see if any of them called that `setPonkaImageToUse()` method.

But what if we simply wanted to say:

> Hey! We just added Ponka to an image!

... add didn't need any information back from possible handlers? In that case we
would create a similar event class, leave *off* the `setPonkaImageToUse()` method
and dispatch it with Messenger. Messenger is perfect if you don't need any info
back from your handlers because... those handlers might end up being called
asynchronously!

If it's *still* not clear to you, just use whichever you want. Why? Because
if you end up wanting your code to run asynchronously, you'll end up choosing
Messenger. And if you want your listeners to be able to communicate back to the
code that dispatches the messages, you'll use EventDispatcher. Otherwise, either
will work.

Next, let's use some service configuration tricks to tighten up how we've organized
our commands, command handlers, events and event handlers.
