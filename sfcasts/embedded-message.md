# Embedded Message

Coming soon...

Deleting things still happen. It's totally synchronously. And you can see it takes a
couple of seconds for it to finish right here. Uh, we did move this into our message
system. We do have a delete image, post message and a delete image post handler. But
inside of our config Messenger Die Yam all, we're not routing that to be async. So
it's being handled immediately. And actually I want to keep this and if you notice,
we're still passing the Insti object into the argument I, we talked a second ago
about how it's really make the message pure. You should probably just pass in the ID
and then your handle or query or that entity. But if you're planning on keeping
delete image post synchronous, it's up to you. It doesn't hurt anything to do this
when you know that a message is going to be handled synchronously. And really we need
this message to be handled synchronously because deleting an image is actually two
steps.

Um, first you actually need to remove it from the database. And second, you need to
remove the underlying file wherever you stored it. But really if you think about it,
only one of these two parts needs to happen immediately. We need to remove it from
the database immediately. If we cued that for later, then you might end up with a
weird possibility where we hit delete here and it disappears. But if the user
refreshes the page, it's still in the database so it shows up. So we do need to
probably remove it from the database immediately, but we don't need to actually
delete the file right now. User doesn't. If we do from the database, the user doesn't
know or care that the file still exists. We can delete it five minutes later, a day
later, it doesn't matter. So what I want to do is actually split this into two
different pieces.

So let's create a new message class called delete photo file and here or in create
Pollock function construct so we can pass in the information that we need. Now if you
look at our handler here, in order to delete a um, an image you really need to Pat,
we really need is the photo manager and you needed to pass it. The image file name.
So technically speaking, the image file names string is all we need to pass to our
delete photo file. So let's say string file name, I'll have all entered and go to
initialize fields to create that property and set them down here. I'll do command and
our cogeneration and I'll generate that. Get her perfect. So a nice simple class here
and let's create the message handler for this. So I've created a class called delete
photo file handler. And remember this newly needs to file a two rules. First we need
to implement message handler interface, then we now have an method that has

what they typed into up delete photo file, delete photo file. Perfect. Now the only
thing we need to do, and here it's very simple as we just based on the need to call
this one line, this air photo manager Eric delete image.

So I'll copy that and I'll paste that into my handler. And then here for the file
name will actually now be delete photo file or message object. And it happens to have
the same uh, method on it and get file name. And of course you get the photo manager.
Since this is a service, this is the same thing we did before. We'll create a
constructor and I'll type it photo file manager, Florida manager, and do the same
thing all to enter initialize fields to create that property and set it. Cool. So we
now have a functional class which has a string filename needed and a handler which
reads that string file name and calls the phone manager, delete it.

So for the next step, there's actually two things we can do. You might be thinking
that in my image posts controller, I might delete too. I might actually dispatched
two different messages, but really all I should have to do my controller. It's just
say I want to delete the image post inside of that handler. It might then decide to
break the functionality again into multiple pieces. So we're going to do here is
instead of calling the phone Amanda Delete Image, we're actually going to get the
message bus from inside of his handler by typing message US interface message bus.

Now update all the property. I'll update the property name and then we're actually
going to use that to dispatch a message down here. So I'll remove the delete image
line and I'll just say filename = image post Arrow, get file name and then I'll
delete it from the database. And then down here I'll say this->message bus aero
dispatch, you delete photo file, you would delete photo file and pass that file name.
All right, so right now that should work. Everything is totally synchronous, but if I
go over here, I'll just refresh the page just to be extra careful.

If we delete the first one it works. I'll refresh here and yes it has gone great. Now
one thing that might be a little weird here, one thing I want you to be thinking
about here is that if there's some problem removing this image post in the database,
it's going to have an air right here and it's never going to go and dispatch our
message down there. But if this is six, if this is successfully moved from the
database, but then there's a problem deleting the file while it is going to be
deleted from the database, but it's never, the file might never actually be deleted.
So if you were going to keep all this synchronously knew, cared about that you might
wrap this entire thing in a doctrine transaction to make sure that everything was
fully successful, including deleting the file before you actually flushed it.
However, we're not going to worry about that because what I'm actually going to do is
make this delete photo file be handled asynchronously, which means that basically
there's no way that this last line is going to fail because all this last line is
going to do is make sure that it sends over to the queue. Oh, now that I say that, I
realized that you could send to the Q and a can fail to send to the queue. So you
might still need a doctrine transaction.

I'll have to think about if I want to explain all that. And also the fact that this
touches a bit on retries. So now that we've broken this into two small pieces, we can
go into our config packages. Messenger.yaml. I'm going to copy our routing line here
and now we can route delete photo file to be ASYNC. They should delete it immediately
from the database but then don't get the vile a few seconds later. So because we just
made a change to some handler code, I'm going to go over and stop our worker and
restart it so it sees the new code. Now let's go over here or refresh the page just
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
just gets mixed up. So you can see there's ad punk to image things are being handled
here and delete photo files are being handled and it's just reading those things off
the queue in the order that they were received. By the way, you'll notice that we now
have like one class, um, every class has its own routing line and that's probably how
you're gonna want to organize things. How are, if you end up with a lot of message
classes and you're constantly making them Async, this routing key does work via
interfaces. So what that means is you could actually create a interface, for example,
instead of your message class called your async message interface. And then you could
actually put that down here. Something like async message interface pointed the
ASYNC. And if you did this than any classes that implemented, the interface would go
to that, uh, would go to that. Would we get routed to vet transport? So that's just a
little trick up your sleeve if you need that.