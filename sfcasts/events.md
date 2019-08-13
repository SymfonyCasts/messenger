# Creating & Handling Events

So... what the heck *is* an event? Let me give you an example. Suppose a user
registers on your site. When that happens, you do three things: save the user
to the database, send them an email and add them to a CRM system. The code to do
this might all live in a controller, a service *or* a `SaveRegisteredUserHandler`
if you had a `SaveRegisteredUser` command.

This means that your service - or maybe your command handler - is doing *three*
separate things. That's... not a huge deal. But if you need to suddenly do a *fourth*
thing, you'll need to add even *more* code. Your service - or handler - violates
the single responsibility principle that says that each function should only have
to accomplish a *single* task.

This is *not* the end of the world - I often write code like this... and it doesn't
usually bother me. But this code organization problem is *exactly* why events exist.

Here's the idea: if you have a command handler like `SaveRegisteredUser`, it's
*supposed* to only perform its *principle* task: it should save the registered
user to the database. If you follow this practice, it should *not* do "secondary"
tasks, like emailing the user or setting them up in a CRM system. Instead, it
should perform the main task and then dispatch an event, like `UserWasRegistered`.
Then, we would have *two* handlers for that event: one that sends the email and
one that sets up the user in the CRM. The command handler performs the main "action"
and the event helps other parts of the system "react" to that action.

As far as Messenger is concerned, commands and events all look identical. The
difference comes down to each supporting a different *design* pattern.

## The Secondary Task of DeleteImagePostHandler

And... *we* already have a situation like this! Look at `DeleteImagePost` and then
`DeleteImagePostHandler`. The "main" job for this handler is to remove this
`ImagePost` from the database. But it *also* has a second task: deleting the
underlying file from the filesystem.

To do that, well, we're  dispatching a *second* command - `DeletePhotoFile` - and
*its* handler deletes the file. Guess what... this is the event pattern! Well,
it's *almost* the event pattern. The only difference is the *naming*: `DeletePhotoFile`
sounds like a "command". Instead of "commanding" the system to do something,
an event is more of an "announcement" that something *did* happen.

To fully understand this, let's back up and re-implement all of this fresh.
Comment out the `$messageBus->dispatch()` call and then remove the `DeletePhotoFile`
use statement on top. 

[[[ code('a6a76aae83') ]]]

Next, to get a clean start: remove the `DeletePhotoFile` command class itself 
and `DeletePhotoFileHandler`. Finally, in `config/packages/messenger.yaml`, 
we're routing the command we just deleted. Comment that out.

[[[ code('dfe1ab5a10') ]]]

Let's look at this with fresh eyes. We've successfully made `DeleteImagePostHandler`
perform is *primary* job only: deleting the `ImagePost`. And now we're wondering:
where should I put the code to do the *secondary* task of deleting the physical
file? We could put that logic right here, or leverage an *event*.

## Creating the Event

Commands, events & their handlers look identical. In the `src/Message`
directory, to start organizing things a bit better, let's create an `Event/`
subdirectory. Inside, add a new class: `ImagePostDeletedEvent`.

[[[ code('86fdc32311') ]]]

Notice the *name* of this class: that's *critical*. Everything so far has sounded
like a command: we're running around our code base shouting: `AddPonkaToImage`!
And `DeleteImagePost`! We sound bossy.

But with events, you're not using a strict command, you're notifying the system
of something that just happened: we're going to fully delete the image post and
*then* say:

> Hey! I just deleted an image post! If you care... uh... now is your chance
> to... uh... do something! But I don't care if you do or not.

The event itself could be handled by... nobody... or it could have *multiple*
handlers. Inside the class, we'll store any data we think might be handy.
Add a constructor with a `string $filename` - knowing the filename of the deleted
`ImagePost` might be useful. I'll hit Alt + Enter and go to "Initialize Fields"
to create that property and set it. Then, at the bottom, I'll go to
"Code -> Generate" - or Command + N on a Mac - and select "Getters" to generate
this one getter.

[[[ code('07ef42914f') ]]]

You may have noticed that, other than its name, this "event" class looks *exactly*
like the command we just deleted!

## Creating the Event Handler

Creating an event "handler" *also* looks identical to command handlers.
In the `MessageHandler` directory, let's create another subdirectory called
`Event/` for organization. Then add a new PHP class. Let's call this
`RemoveFileWhenImagePostDeleted`. Oh... but make sure you spell that all correctly.

[[[ code('3d01d91156') ]]]

This *also* follows a different naming convention. For commands, if a command was
named `AddPonkaToImage`, we called the handler `AddPonkaToImageHandler`. The big
difference between commands and events is that, while each command has exactly
one handler - so using the "command name Handler" convention makes sense - each
event could have *multiple* handlers.

But the inside of a handler looks the same: implement `MessageHandlerInterface`
and then create our beloved `public function __invoke()` with the type-hint for
the event class: `ImagePostDeletedEvent $event`.

[[[ code('1383b84b6b') ]]]

Now... we'll do the work... and this will be identical to the handler we just
deleted. Add a constructor with the one service we need to delete files:
`PhotoFileManager`. I'll initialize fields to create that property then, down
below, finish things with `$this->photoFileManager->deleteImage()` passing that
`$event->getFilename()`.

[[[ code('6a2cf60847') ]]]

I hope this was *delightfully* boring for you. We deleted a command and command
handler... and replaced them with an event and an event handler that are... other
than the name... identical!

Next, let's dispatch this new event... but to our *event* bus. Then, we'll tweak
that bus a little bit to make sure it works perfectly.
