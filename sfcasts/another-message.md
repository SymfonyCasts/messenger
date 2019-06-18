# Another Message

Coming soon...

Yes,

our site is one other thing. You you're for some reason are not happy with your
Ponka image. You can delete it. If you think about a belief, when you click that
button, that does it hits over on this `delete()` endpoint right here, which there's
really two parts of it. One, this `$photoManager->deleteImage()`. This actually takes
care of deleting it from the filesystem. And notice I've added a little `sleep()` here to
make this seem slow because deleting images from the filesystem might be something
that's a little bit heavy, especially if you're deleting it from the, I'm somewhere
in the cloud.

And then it actually deletes it from the database. The other reason I made this
`deleteImage()` methods slow is that if you think about these two pieces and as far as
the user's perspective goes, if you're really important thing is that it disappears
from the screen. Um, it's a really deleting it from the database is the super
important thing that we probably want to get done immediately. And then if it doesn't
actually get deleted from the filesystem for a few seconds or a few minutes later,
that's really more of an internal detail. So we're going to talk more about that
later, but those are actually two different pieces that we can separate to give a
better user experience. So anyways, I'm going to use this new um, command pattern to
move this into a message and a message handler. So weird and not to do this first one
to go into our `Message/` directory, actually going to copy to `AddPonkaToImage` and
we're going to change just to `DeleteImagePost`. I'll update the class name here
because basically it's going to look identical. We're going to need to pass which
image posts we want to delete so that we can get the file name and actually delete
it.

All right, next let's go in and create the handler for that. Great. A new PHP
class, call this `DeleteImagePostHandler`.

And like before we'll give us a `public function __invoke()` with our `DeleteImagePost`
type hint on, on that argument. Now it's the same process as before. I'm going to
copy the UN a move the three lines from our controller that we want to move into our
handler who have those there. And this case we have two dependencies that we need. So
up here and add the `__construct()` method with the arguments of 
`PhotoFileManager $photoManager` and `EntityManagerInterface $entityManager`. 
I'll hit Alt + Enter to initialize both of those fields which creates those properties 
and sets them the `__constructor()`. Now down in here we'll say `$this->photoManager`, 
`$this->entityManager` and one more `$this->entityManager`. And like before we need 
to know which `ImagePosts` we're deleting. So we will say 
`$imagePost = $deleteImagePost->getImagePost()`

perfect. So we have the message, we have a handler. Last step is to go to a
controller and instead of having those four lines of code here, I can delete the last
two arguments. And really it's just a lot simpler on a new argument here for 
`MessageBusInterface $messageBus`. And then our entire controller is 
`$messageBus->dispatch(new DeleteImagePost())` and then pass that the `$imagePost`. 
Pretty cool, right? All right, let's make sure we didn't break anything.
Move over. I'll hit x and ah, check this out. It didn't really move and actually had like 500 air down here.
Um, thanks to the web debug toolbar on the hold command and opened that up and I'll
pop right in there and check this out. It says 

> No handler for message "App\Message\DeleteImagePost"

This is one of the cool things by default, um, you need to have at least one
handler and usually just one handler for each class. And if you don't, it's going to
tell you, hey, something is misconfigured and we can see this. If we go over and run

```terminal
php bin/console debug:messenger
```

it's still only sees our one handler class. The reason
our message classes and shop here is this is really showing you the handlers that it
sees and it just, it doesn't see our handler for some reason. And the reason is that
we are missing our interface here. So add `implements MessageHandlerInterface`

Remember that's a key feature here is Symfony. Now
identifies that as messages, message handler, and now looks at `__invoke()`
method to know that it's there. So we run. Do you `debug:messenger` again? 

```terminal-silent
php bin/console debug:messenger
```

This time it
sees it. All right, so let's go back over here. Close up that profiler. I'm going to
try hitting x again on that this time. It works so great. So we have two messages. We
have two handlers in. Both of our handlers are a little bit heavy because they're
dealing with image manipulations and potentially talking across a network if we're
storing our files on a somewhere in the cloud. So next, I'm gonna Start talking about
transports, which are the key way that we can start doing this work asynchronously so
that we can, the users experiences as fast as possible.