# Dispatching a Message inside a Handler?

Deleting an image is still done synchronously. You can see it: because I made
it *extra* slow for dramatic effect, it takes a couple of seconds to process before
it disappears. Of course, we could hack around this by making our JavaScript remove
the image visually *before* the AJAX call finishes. But making heavy stuff
*async* is a good practice and could allow us to put less load on the web server.

Let's look at the current state of things: we *did* update all of this to be
handled by our command bus: we have a `DeleteImagePost` command and
`DeleteImagePostHandler`. But inside `config/packages/messenger.yaml`, we're not
routing this class anywhere, which means it's being handled immediately.

Oh, and notice: we're *still* passing the entire entity object into the message.
In the last two chapters, we talked about avoiding this as a best practice
*and* because it can cause weird things to happen if you handle this async.

But... if you're planning to keep `DeleteImagePost` synchronous... it's up to
you: passing the entire entity object won't hurt anything. And... really... we
*do* need this message to be handled synchronously! We need the `ImagePost` to
be deleted from the database immediately so that, if the user refreshes, the
image is gone.

But, look closer: deleting involves *two* steps: deleting a row in the database
and removing the underlying image file. And... only that *first* step needs to
happen right now. If we delete the file on the filesystem later... that's no
big deal!

## Splitting into a new Command+Handler

To do *part* of the work sync and the other part async, my preferred approach is
to split this into two commands.

Create a new command class called `DeletePhotoFile`. Inside, add a constructor
so we can pass in whatever info we need. *This* command class will be used to
*physically* remove the file from the filesystem. And if you look in the handler,
to do this, we only need the `PhotoFileManager` service and the string *filename*.

So this time, the *smallest* amount of info we can put in the command class is
`string $filename`. 

[[[ code('9651ebf913') ]]]

I'll hit Alt + enter and go to "Initialize Fields" to create that property and set it. 

[[[ code('5b6f629b2c') ]]]

Now I'll go to Code -> Generate - or Cmd+N on a Mac - to generate the getter.

[[[ code('0356749485') ]]]

Cool! Step 2: add the handler `DeletePhotoFileHandler`. Make this follow the two
rules for handlers: implement `MessageHandlerInterface` and create an `__invoke()`
method with one argument that's type-hinted with the message class:
`DeletePhotoFile $deletePhotoFile`.

[[[ code('cf2005303f') ]]]

Perfect! The *only* thing we need to do in here is... this one line:
`$this->photoManager->deleteImage()`. Copy that and paste it into our handler.
For the argument, we can use our message class: `$deletePhotoFile->getFilename()`.

[[[ code('f91a797e9b') ]]]

And finally, we need the `PhotoFileManager` service: add a constructor with
one argument: `PhotoFileManager $photoManager`. I'll use my Alt+Enter ->
Initialize fields trick to create that property as usual.

[[[ code('745ec700b2') ]]]

Done! We now have a functional command class which requires the string filename,
and a handler that *reads* that filename and... does the work!

## Dispatching Embedded

All we need to do now is *dispatch* the new command. And... *technically* we could
do this in two different places. First, you might be thinking that, in
`ImagePostController`, we could dispatch two different commands right here.

But... I don't *love* that. The controller is already saying `DeleteImagePost`.
It shouldn't need to issue any other commands. If we choose to break that logic
down into *smaller* pieces, that's up to the handler. In other words, we're going
to dispatch this new command from *within* the command handler. Inception!

Instead of calling `$this->photoManager->deleteImage()` directly, change the
type-hint on that argument to autowire `MessageBusInterface $messageBus`.
Update the code in the constructor... and the property name.

[[[ code('288755011c') ]]]

Now, easy: remove the old code and start with:
`$filename = $imagePost->getFilename()`. Then, let's delete it from the database
and, at the bottom, `$this->messageBus->dispatch(new DeletePhotoFile($filename))`.

[[[ code('fb8f83c8a0') ]]]

And... this should... just work: everything is *still* being handled synchronously.

Let's try it next, think a bit about what happens if *part* of a handler fails,
and make half of the delete process async.
