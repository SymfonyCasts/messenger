# Message, Handler & debug:messenger

Our app has one other small superpower. If for *some* reason you're not happy with
your Ponka image... I'm not even sure *how* that would be possible... you can
delete it. When you click that button, it sends an AJAX request that hits this
`delete()` action.

And... that really does two things. First, `$photoManager->deleteImage()` takes
care of physically deleting the image from the filesystem. I added a `sleep()`
for dramatic effect, but deleting something from the filesystem *could* be a bit
heavy if the files were stored in the cloud, like on S3.

And second, the controller deletes the `ImagePost` from the database. But... thinking
about these two steps... the only thing we need to do *immediately* is delete the
image from the database. If we *only* did that and the user refreshed the page,
it *would* be gone. And then... if we deleted the actual file a few seconds... or
minutes or even *days* later... that would be totally fine! But... more on doing
fancy asynchronous stuff in a few minutes.

## Creating DeleteImagePost

Right now, let's refactor all this deleting logic into the command bus pattern we
just learned. First, we need the message, or "command" class. Let's copy
`AddPonkaToImage`, paste and call it `DeleteImagePost.php`. Update the class name
and then... um... do *nothing*! Coincidentally, this message class will look
*exactly* the same: the handler will need to know *which* `ImagePost` to delete.

[[[ code('36487e6995') ]]]

## Creating DeleteImagePostHandler

Time for step 2 - the handler! Create a new PHP class and call it
`DeleteImagePostHandler`. Like before, give this a `public function __invoke()`
with a `DeleteImagePost` type-hint as the only argument.

[[[ code('6e39ab5f03') ]]]

Now, it's the same process as before: copy the first three lines of the controller,
delete them, and paste them into the handler. This time, we need two services. 

[[[ code('b794098fc1') ]]]

Add `public function __construct()` with `PhotoFileManager $photoManager` and
`EntityManagerInterface $entityManager`. I'll hit Alt + Enter and click initialize
fields to create both of those properties and set them.

[[[ code('f045313514') ]]]

Down here, use `$this->photoManager`, `$this->entityManager` and one more
`$this->entityManager`. And, like before, we need to know which `ImagePost` we're
deleting. Prep that with `$imagePost = $deleteImagePost->getImagePost()`.

[[[ code('94349efc49') ]]]

## Dispatching the Message

Ding! That's my... it's done sound! Because, we have a message, a handler and Symfony
*should* know that they're linked together. The *last* step is to *send* the message.
In the controller... we don't need these last two arguments anymore... we *only*
need `MessageBusInterface $messageBus`. And then, this is *wonderful*, our *entire*
controller is: `$messageBus->dispatch(new DeleteImagePost($imagePost))`.

[[[ code('07378e449c') ]]]

Pretty cool, right? Let's see if it all works. Move over, click the "x" and... hmm...
it didn't disappear. And... it looks like it was a 500 error! Through the power
of the profiler, we can click the little link to jump *straight* to a big, beautiful,
HTML version of that exception. Interesting:

## Command Bus: Each Message should have One Handler

> No handler for message `App\Message\DeleteImagePost`

That's interesting. Before we figure out what went wrong, I want to mention one
thing: in a command bus, each message *normally* has exactly *one* handler: not
two and not zero. And *that's* why Messenger gives us a helpful error if it can't
find that handler. We'll talk more about this later and *bend* these rules when
we talk about event buses.

## Debugging the Missing Handler

Anyways... why does Messenger think that `DeleteImagePost` doesn't have a handler?
Can't it see the `DeleteImagePostHandler` class? Find your terminal and run:

```terminal
php bin/console debug:messenger
```

Woh! It only sees our *one* handler class! What this command *really* does is this:
it finds *all* the "handler" classes in the system, then prints the "message"
that it handles next to it. So... this confirms that, for some reason, Messenger
doesn't see our handler!

And... you may have spotted my mistake! To find all the handlers, Symfony looks
in the `src/` directory for classes that implement `MessageHandlerInterface`. And...
I forgot that part! Add `implements MessageHandlerInterface`.

[[[ code('b783fdcfeb') ]]]

Run `debug:messenger` again:

```terminal-silent
php bin/console debug:messenger
```

*Now* it sees it! Let's try it again: close up the profiler, try hitting "x"
and... this time it works!

Status report: we have two messages and each has a handler that's potentially doing
some pretty heavy work, like image manipulation or talking across a network if
files are stored in the cloud. It's time to talk about *transports*: the key
concept behind taking this work and doing it asynchronously so that our users
don't have to wait for all that heavy work to finish before getting a response.
