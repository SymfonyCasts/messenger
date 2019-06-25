# Embedded Message

Coming soon...

Deleting things still happen. It's totally synchronously. And you can see it takes a
couple of seconds for it to finish right here. Uh, we did move this into our message
system. We do have a `DeleteImagePost` message and a `DeleteImagePostHandler`. But
inside of our `config/messenger.yaml`, we're not routing that to be `async`. So
it's being handled immediately. And actually I want to keep this and if you notice,
we're still passing the entity object into the argument I, we talked a second ago
about how it's really make the message pure. You should probably just pass in the `id`
and then your handle or query or that entity. But if you're planning on keeping
`DeleteImagePost` synchronous, it's up to you. It doesn't hurt anything to do this
when you know that a message is going to be handled synchronously. And really we need
this message to be handled synchronously because deleting an image is actually two
steps.

Um, first you actually need to remove it from the database. And second, you need to
remove the underlying file wherever you stored it. But really if you think about it,
only one of these two parts needs to happen immediately. We need to remove it from
the database immediately. If we queued that for later, then you might end up with a
weird possibility where we hit delete here and it disappears. But if the user
refreshes the page, it's still in the database so it shows up. So we do need to
probably remove it from the database immediately, but we don't need to actually
delete the file right now. User doesn't. If we do from the database, the user doesn't
know or care that the file still exists. We can delete it five minutes later, a day
later, it doesn't matter. So what I want to do is actually split this into two
different pieces.

So let's create a new message class called `DeletePhotoFile` and here or in create
`public function __construct()` so we can pass in the information that we need. Now if you
look at our handler here, in order to delete a um, an image you really need to Pat,
we really need is the photo manager and you needed to pass it. The image filename.
So technically speaking, the image filename string is all we need to pass to our
`DeletePhotoFile`. So let's say `string $filename`, I'll have Alt + enter and go to
Initialize Fields to create that property and set them down here. I'll do command and
our cogeneration and I'll generate that getter perfect. So a nice simple class here
and let's create the message handler for this. So I've created a class called 
`DeletePhotoFileHandler`. And remember this newly needs to file a two rules. First we need
to implement `MessageHandlerInterface`, then we now have `__invoke()` method that has

what they typed into up `DeletePhotoFile $deletePhotoFile`. Perfect. Now the only
thing we need to do, and here it's very simple as we just based on the need to call
this one line, `$this->photoManager->deleteImage()`.

So I'll copy that and I'll paste that into my handler. And then here for the file
name will actually now be `$deletePhotoFile` or message object. And it happens to have
the same uh, method on it and `getFilename()`. And of course you get the photo manager.
Since this is a service, this is the same thing we did before. We'll create a
`__constructor()` and I'll type it `PhotoFileManager $photoManager`, and do the same
thing Alt + Enter -> Initialize fields to create that property and set it. Cool. So we
now have a functional class which has a string filename needed and a handler which
reads that string filename and calls the Photo manager, delete it.

So for the next step, there's actually two things we can do. You might be thinking
that in my `ImagePostsController`, I might delete too. I might actually dispatched
two different messages, but really all I should have to do my controller. It's just
say I want to delete the image post inside of that handler. It might then decide to
break the functionality again into multiple pieces. So we're going to do here is
instead of calling the Photo Manager `->deleteImage()`, we're actually going to get the
message bus from inside of his handler by typing `MessageBusInterface $messageBus`.

Now update all the property. I'll update the property name and then we're actually
going to use that to dispatch a message down here. So I'll remove the delete image
line and I'll just say `$filename = $imagePost->getFilename()` and then I'll
delete it from the database. And then down here I'll say 
`$this->messageBus->dispatch(new DeletePhotoFile())`and pass that `$filename`.
All right, so right now that should work. Everything is totally synchronous, but if I
go over here, I'll just refresh the page just to be extra careful.

If we delete the first one it works. I'll refresh here and yes it has gone great. Now
one thing that might be a little weird here, one thing I want you to be thinking
about here is that if there's some problem removing this `ImagePost` in the database,
it's going to have an air right here and it's never going to go and dispatch our
message down there. But if this is six, if this is successfully moved from the
database, but then there's a problem deleting the file while it is going to be
deleted from the database, but it's never, the file might never actually be deleted.
So if you were going to keep all this synchronously knew, cared about that you might
wrap this entire thing in a doctrine transaction to make sure that everything was
fully successful, including deleting the file before you actually flushed it.
However, we're not going to worry about that because what I'm actually going to do is
make this `DeletePhotoFile` be handled asynchronously, which means that basically
there's no way that this last line is going to fail because all this last line is
going to do is make sure that it sends over to the queue. Oh, now that I say that, I
realized that you could send to the Q and a can fail to send to the queue. So you
might still need a doctrine transaction.

I'll have to think about if I want to explain all that. And also the fact that this
touches a bit on retries. So now that we've broken this into two small pieces, we can
go into our `config/packages/messenger.yaml`. I'm going to copy our routing line here
and now we can route `DeletePhotoFile` to be `async`. They should delete it immediately
from the database but then don't get the vile a few seconds later. So because we just
made a change to some handler code, I'm going to go over and stop our worker and
restart it so it sees the new code.

```terminal-silent
php bin/console messenger:consume -vv
``` 
 
Now let's go over here or refresh the page just
to be sure and let's try to leading check out how much faster that is. Now there's no
longer a delay there if we scoot over here. Yeah, you can see it doing all kinds of
good stuff here and actually you can see it as an air and exception occurred while
handling message because a file was not found a, I believe that was actually due to
the case where we had the duplicated row in the database and you can see it's
actually retrying that file get.

We're going to talk about retries and second and how to handle those.

So if you put the whole system together and at the bottom and actually reject rejects
it. So if you put the whole system got here, we can upload a bunch of files. Then
down here I'll delete a bunch of our older files and all of this in our messenger
just gets mixed up. So you can see there's `AddPonkaToImage` things are being handled
here and `DeletePhotoFile` are being handled and it's just reading those things off
the queue in the order that they were received. By the way, you'll notice that we now
have like one class, um, every class has its own routing line and that's probably how
you're gonna want to organize things. How are, if you end up with a lot of message
classes and you're constantly making them Async, this routing key does work via
interfaces. So what that means is you could actually create a interface, for example,
instead of your message class called your `AsyncMessageInterface`. And then you could
actually put that down here. Something like `Async=MessageInterface` pointed the
`async`. And if you did this than any classes that implemented, the interface would go
to that, uh, would go to that. Would we get routed to vet transport? So that's just a
little trick up your sleeve if you need that.