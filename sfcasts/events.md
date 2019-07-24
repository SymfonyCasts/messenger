# Events

Coming soon...

I want to talk about inside of our `DeleteImagePostHandle` up, we're really doing
two things. We're removing the `ImagePost` from the database. And the second thing
we're doing is we're actually deleting the physical file from the filesystem and
we're actually doing that by dispatching, um, another command onto our command bus.
This would be, this could be considered the secondary work, deleting the `ImagePost`
with databases, the primary job of the `DeleteImagePostHandler` and anything else
could, uh, like the lead in the file itself is sort of secondary work. So I'm gonna
refactor this secondary work into um, taking advantage of events and event buses. So
I'm going to start by con basically pretending like we never did the second part. So
I'm gonna comment out this, uh, `$messageBus->dispatch()` here and I can now remove
the a `DeletePhotoFile` use statement on top and I'm literally going to delete the,
uh, `DeletePhotoFile` message class itself and the `DeletePhotoFileHandler`. And
then the last change we need to make is in `config/packages/messenger.yaml`. We
have routing for this now non-existent class. `DeletePhotoFile` from just going to
comment that out.

So at this point, this is sort of like a halfway done and we have our 
`DeleteImagePost` command and we have our `DeleteImagePostHandler` and its doing only
its primary job of deleting image posts. So imagine that this is a [inaudible].
Imagine that we're creating this delete functionality for the first time. And now
we're sort of halfway done. We've gotten to the point where we've done our primary
job and we're asking ourselves how can we do the secondary job of deleting the actual
file off of the filesystem, which we could just put in here. Um, it's a matter of
preference. So let's do this. We're going, we're going to dispatch and event.

And again, the difference between command and events is just really a kind of subtle
design pattern. So just like with commands, we're going to create a com, a class, a
message class and then we're gonna create a handler for that inside your message
directory. Just start kind of organizing things a little bit better. I'm going to
create an `Event/` subdirectory and inside of there and new piece reclass called 
`ImagePostDeletedEvent`. Now notice the naming convention on that. Everything so far has
actually sounded like a command `AddPonkaToImage` or `DeleteImagePost`. But with
events what you're doing is you're describing something that happened already because
we're actually going to delete the image post and then dispatch the event. So it's
very common to have somebody like Image post deleted event. You're describing what
happened a moment ago and inside of here we'll put whatever data we think might be
relevant to people that want to listen than want to do something when an image post
is deleted. So really at this point there's not much on an image post other than the
`string $filename`.

So I'll, I'll hit Alt + Enter and go to Initialize Fields to create that property. And then
on the bottom I'll go to Code Generate or Command + N on a Mac and go to Getters and
generate the getter for that file. Name happens to be that. This pretty much looks
exactly like our command class was a second ago. So `ImagePostDeletedEvent`, we're
also going to create the handler in the normal way. So when the message handler, once
again, I'm going to create a sub directory called `Event/` for organization and then a
new PHP class. And let's call this about `RemoveFileWhenImagePostDeleted`.

Oh, and let me actually go to [inaudible] factor that. So I actually spelled the word
post correctly. Now notice here, this is also a different naming convention with the
commands we are doing `AddPonkaToImage` and `AddPonkaToImageHandler`. And the big
difference between commands and events is that with a command there's always one that
command class in one handler. So kind of following this convention of a calling, the
command class one thing and then just adding a more handle or on the end makes sense.
But with events you can have many different uh, handlers for a single, uh, event
class, which is why it's common to do a naming convention that kind of describes what
you're doing when that event happened.

Now actually making this a a handler is gonna work the same way as commands. If
you're going to implement `MessageHandlerInterface`. And then we're going to our
classic `public function __invoke()` and then we're going to type hint this with
our m event class, which is `ImagePostDeletedEvent`, almost call that argument
`$event`.

And now to do our work, this is going to be pretty identical to the other handler we
just deleted. We're going to add a constructor and the one piece, um, one service we
need to delete files as the `PhotoFileManager`. And so I'll add that as an argument.
Go to all the enter initialized fields to add that as an argument. And then down here
it's as simple as `$this->photoFileManager->deleteImage()` and then we can say
`$event->getFilename()`. So if you actually compared our event class and our you, our
event handler to the command and command handler, we just deleted, they're almost
identical. The biggest difference is really just this naming convention and the fact
that okay, it's just this naming convention. Now back in `DeleteImagePostHandler`.
What we want to do is actually dispatched our new `ImagePostDeletedEventMessage`.
Now you remember from earlier we created a two buses. So we now just for organization
have uh, a command bus, which is called `Messenger.bus.default`. You have our new
thing called `event.bus`. Uh, when we just type in `MessageBusInterface`, it's going to
give us that first bus.

But if you're looking at `debug:autowiring`

```terminal-silent
php bin/console debug:autowiring mess
```

do you remember that if we give, if
we use the type N and the argument name, `$eventBus`, it's going to give us our new
`event.bus`. So we're going to go into `DeleteImagePostHandler`. I'm gonna change the
argument to `$eventBus`, event bus. And I'm also going to refactor, do the same thing
on the property. Make the property call the `$eventBus` as well. Well, I'll make sure I
had my dollar sign on the actual, on the argument. Perfect. And now the bottom is
going to be really the same thing as before, `$this->eventBus->dispatch()` and the 
`new ImagePostDeletedEvent()`. And we'll pass that `$finalname` CNCS, almost just a renaming
of things. This is really the same thing as with the commands. The key thing from a
philosophical level is that this handler is going to do its primary job and it's
going to say I'm done. Then down here by dispatching the vent, what it's saying is I,
the Image post has been deleted. If anyone else cares and wants to do something, you
can do something.

This hand, when we dispatch somebody to the event bus, we actually don't care if
there are any handlers to where there could be zero handlers, we could just add
dispatch something onto the event bus just in case. Or there could be five handlers
or 10 handlers. Um, we don't care what this does, it's not our concern. And obviously
we're not using any return value from it or anything else. You really are just
screaming out into space, Hey, the Image Post was deleted. If you care, do something
with it. And if you do something with it, I don't care. I'm just throwing out a flag
to tell you that the image post was deleted. And the last thing we need to do to make
this completely identical to what we did before was in `config/packages/messenger.yaml`
we're going to update our, uh, are a routing down here too. Um, route 
`App\Messages\Event\ImagePostDeletedEvent` to be `async` at this point it should all
work the same. So I'm gonna move over here, I'll go find my worker, restart that. And
we just refactored the delete stuff. So if I refresh this page should be able to
delete a couple of things and moved back over here.

And yes, everything's working perfectly and you can see the desired 
`ImagePostDeletedEvent` is actually what is being dispatched and handle.

By the way, with um, with routing. When you ran it a command class, you always knew
the one exact handler that was there. So it was very easy to determine whether or not
that handler could be async. With events, it's a little bit different because this
event could technically have multiple handlers. And in theory you might want some
handlers be handled synchronously and some to be handled asynchronously. Uh, the way
that Messenger is built, you do the routing based on the message class, not the
handler. However, we're not going to talk about it in this tutorial, but it is
possible to have, um, to have a, if a, if an event has multiple handlers to make some
of those handlers handle sync and some of them handle Async, you can find that in the
documentation.

Now, one of the PR, one of the other differences. So one of the big differences
between the command bus and the event bus. Um, ah, thanks to the philosophical
differences that event, when you dispatch an event, it's possible that there can be
no handlers and you don't really care. But check this out. I'm going to go to my
`RemoveFileWhenImagePostDeleted` and I'm going to delete the 
`implements MessageHandleInterface`. But I'm basically going to create a, uh, I'm going to, and I'm
doing this just to, so that as soon as I do this Symfony, we'll no longer see this as
a, a event handler. So I'm just doing this temporarily. So that `ImagePostDeletedEvent`
has no handlers cause I want to see what happens. So if we go back now and try
to delete it works. But if you go over here, oh, and actually what I needed to do is
actually restart my work before I do this.

Let, let me try that again. So I'm gonna delete another image. It works, but then it
sends our new event, the queue and it actually airs out because it says an exception
occurred while handling a `ImagePostDeletedEvent`, no handler form message. So by
default, the message buses really work. Um, the message buses require you to have at
least one handle. And this is kind of a mechanism, but on a technical level, event
buses shouldn't need to have a, uh, a handler. So to make our event and bus actually
behave more like an `event.bus`, we take off that til day and do a little of extra
configuration. Below that we can say `default_middleware: allow_no_handlers`. This is a
bit of a magic key that basically says, I want to use all the default middleware,
but tell the middleware that it's okay if there are no handlers for this.

So I'll go back and restart my worker and then delete one in the summit that comes.
Yep. It says, you can see it says no handler for message, but it doesn't freak out
anymore. So you can see now the command bus and the, uh, event bus have a subtle
difference on how they behave, but there really are pretty much the same. So I'm
gonna go back here and put back my message handle on her face and then restart my
worker. Now one other question is, uh, with the, this event bus pattern is what is
the difference between using one minute use Symfony's event bus versus Symfonys event
dispatcher in this is a really loaded question. So the purpose have, I'm Symfony's
event dispatcher, which is an older component and Symfony is basically the same as an
event. Boston, it's a hook point. So historically the event dispatcher's been used.

Um, when you want to tell your system that something happened and have other people
listen to it identical to what we're doing here with the event bus. So the difference
between when you should use the event dispatcher and when you should use the event
bus is very subtle. The one practical difference between the event dispatcher and the
event bus is that, uh, listeners to the event dispatcher are called listeners, not
handlers, um, can't be async any listener to the event just to events with the event
dispatcher are always handled synchronously. So using an event bus, so with Messenger
you get some added flexibility because your handlers to the events can be a
synchronous. But the biggest difference between should I use the event dispatcher or
should I use an event bus, is the following. I would use the event dispatcher if I
wanted the listeners to that event to be able to communicate information back to me.
So for example, if we wanted to have, for example, let's go, let's go to um, 
`AddPonkaToImageHandler`.

Let's suppose that before we actually add Ponca to an image, we want to allow other
parts of our system to make some sort of a change to the image. Maybe it actually
changes the file name or something. I'm not sure exactly what to do. So like right
here, uh, one other parts of the system to be able to tell us which ponka image to
use. Something like that. Something where we're going to dispatch an event and
somebody is going to give that information back to us. That is better used as an
event dispatcher because it has a built, it really just the nature of the listeners
being synchronous. It's really better for a situation where you actually are, are
saying something's happening and you want information back. If you simply don't just
want to say something happened that's a little bit better for the uh, event bus, but
really you can't go wrong. These are very, very subtle differences. And actually one
PSR 14 was being debated. Is this something that was debated for very long, long
time? Cause there absolutely is overlap. So if you're not sure which one to use,
don't sweat it. You use either one. Um, but remember that the you using messenger is
going to give you the added flexibility of asynchronous messages.