# Another Message

Coming soon...

Yes,

our site is one other thing. You you're for some reason are not happy with your
pumpkin image. You can delete it. If you think about a belief, when you click that
button, that does it hits over on this delete end point right here, which there's
really two parts of it. One, this photo manager delete image. This actually takes
care of deleting it from the filesystem. And notice I've added a little sleep here to
make this seem slow because deleting images from the filesystem might be something
that's a little bit heavy, especially if you're deleting it from the, I'm somewhere
in the cloud.

Okay.

And then it actually deletes it from the database. The other reason I made this
delete image methods slow is that if you think about these two pieces and as far as
the user's perspective goes, if you're really important thing is that it disappears
from the screen. Um, it's a really deleting it from the database is the super
important thing that we probably want to get done immediately. And then if it doesn't
actually get deleted from the filesystem for a few seconds or a few minutes later,
that's really more of an internal detail. So we're going to talk more about that
later, but those are actually two different pieces that we can separate to give a
better user experience. So anyways, I'm going to use this new um, command pattern to
move this into a message and a message handler. So weird and not to do this first one
to go into our message directory, actually going to copy to add pumpkin image and
we're going to change just to delete image post. I'll update the class name here
because basically it's going to look identical. We're going to need to pass which
image posts we want to delete so that we can get the file name and actually delete
it.

All right, next let's go in and create the handler for that. Great. A new petri
class, Clovis deletes image post handler.

Okay.

And like before we'll give us a public function invoke with our delete image post
type hint on, on that argument. Now it's the same process as before. I'm going to
copy the UN a move the three lines from our controller that we want to move into our
handler who have those there. And this case we have two dependencies that we need. So
up here at nab deconstruct method with the arguments of photo file manager, photo
manager and entity manager, interface entity manager. I'll hit all to enter to
initialize both of those fields which creates those properties and sets them the
constructor. Now down in here we'll say this air photo manager, this era anti manager
and one more this->into manager. And like before we need to know which image posts
we're deleting. So we will say image post = bleed image post Arrow, get image post

perfect. So we have the message, we have a handler. Last step is to go to a
controller and instead of having those four lines of code here, I can delete the last
two arguments. And really it's just a lot simpler on a new argument here for message,
bus interface and message bus. And then our entire controller is message bus. Aero
dispatch knew, delete image post and then pass that the image post. Pretty cool,
right? All right, let's make sure we didn't break anything. Move over. I'll hit x and
ah, check this out. It didn't really move and actually had like 500 air down here.
Um, thanks to the web debug toolbar on the hold command and opened that up and I'll
pop right in there and check this out. It says no handler for message delete image
posts. This is one of the cool things by default, um, you need to have at least one
handler and usually just one handler for each class. And if you don't, it's going to
tell you, hey, something is misconfigured and we can see this. If we go over and run
bin Console, debug messenger, it's still only sees our one handler class. The reason
our message classes and shop here is this is really showing you the handlers that it
sees and it just, it doesn't see our handler for some reason. And the reason is that
we are missing our interface here. So add implements,

message handler interface. Remember that's a key feature here is Symfony. Now
identifies that as messages, message handler, and now looks at the_underscore, invoke
method to know that it's there. So we run. Do you debug messenger again? This time it
sees it. All right, so let's go back over here. Close up that profiler. I'm going to
try hitting x again on that this time. It works so great. So we have two messages. We
have two handlers in. Both of our handlers are a little bit heavy because they're
dealing with image manipulations and potentially talking across a network if we're
storing our files on a somewhere in the cloud. So next, I'm gonna Start talking about
transports, which are the key way that we can start doing this work asynchronously so
that we can, the users experiences as fast as possible.