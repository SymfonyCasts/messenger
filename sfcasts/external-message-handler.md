# Setup for Messages from an Outside System

What if a queue on RabbitMQ was filled with messages that originated from an
*external* system... but we wanted to consume and *handle* those from our Symfony
App? For example, maybe a user can request that a photo be deleted from some
totally different system... and that system needs to communicate *back* to our app
so that it can actually *do* the deleting? How would that work?

Each transport in Messenger really has *two* jobs: one, to send messages to a
message broker or queue system and two, to *receive* messages from that same
system and handle them.

And, like we talked about in the last video, you don't need to use *both* features
of a transport: you could choose to *send* to a transport, but never read and
*consume* those messages... because some other system will. Or, you can do the
opposite: create a transport that you will never *send* to, but that you *will*
use to *consume* messages... that were probably put there by some outside system.
The *trick* to doing this is creating a serializer that can understand the
*format* of those outside messages.

## Creating a new Message & Handler

Instead of over-explaining this, let's see it in action. First, pretend that
this imaginary external system needs to be able to tell our app to do something...
very... important: to log an Emoji. Ok, this may not be the *most* impressive type
of a message... but the details of *what* this outside message is telling our app
to do aren't important: it could be telling us to upload an image with details about
where the file is located, delete an image, send an email to a registered user
or, log an emoji!

Let's get this set up. Normally, if *we* wanted to dispatch a command to log an
Emoji, we would start by creating a message class and message handler. In this
case... we'll do the *exact* same thing. In the `Command/` directory, create a
new PHP class called `LogEmoji`. 

[[[ code('a58117d08f') ]]]

Add a `public function __construct()`. In order to tell us *which* emoji to log, 
the outside system will send us an integer *index* of the emoji they want - our app 
will have a list of emojis. So, add an `$emojiIndex` argument and then press Alt+Enter 
and select "Initialize Fields" to create that property and set it.

[[[ code('7f74b29e94') ]]]

To make this property *readable* by the handler, go to the Code -> Generate menu -
or Command + N on a Mac - select getters and generate `getEmojiIndex()`.

[[[ code('018a56280b') ]]]

Brilliant! A *perfectly* boring, um, normal, message class. Step two: in the
`MessageHandler/Command/` directory, create a new `LogEmojiHandler` class.
Make this implement our normal `MessageHandlerInterface` and add
`public function __invoke()` with the type-hint for the message: `LogEmoji $logEmoji`.

[[[ code('b8027c7613') ]]]

Now... we get to work! I'll paste an emoji list on top: here are the five that
the outside system can choose from: cookie, dinosaur, cheese, robot, and of course,
poop. 

[[[ code('d208be46b1') ]]]

And then, because we're going to be logging something, add an `__construct()`
method with the `LoggerInterface` type hint. Hit Alt + Enter and select
"Initialize Fields" one more time to create *that* property and set it.

[[[ code('5fc4f8d7fb') ]]]

Inside `__invoke()`, our job is pretty simple. To get the emoji, set an
`$index` variable to `$logEmoji->getEmojiIndex()`. 

[[[ code('cb646db087') ]]]

Then `$emoji = self::$emojis` - to reference that static property -
`self::$emojis[$index] ?? self::emojis[0]`.

[[[ code('294d01d24f') ]]]

In other words, *if* the index exists, use it. Otherwise, fallback to logging a
cookie... cause... everyone loves cookies. Log with
`$this->logger->info('Important message! ')`and then `$emoji`.

[[[ code('9a1987843c') ]]]

The *big* takeaway from this new message and message handler is that it is, well,
absolutely *no* different from *any* other message and message handler! Messenger
does *not* care whether the `LogEmoji` object will be dispatched manually from
our own app or if a worker will receive a message from an outside system that
will get mapped to this class.

To prove it, go up to `ImagePostController`, find the `create()` method and,
*just* to see make sure this is working, add:
`$messageBus->dispatch(new LogEmoji(2))`.

[[[ code('e9560eb72c') ]]]

If this *is* working, we should see a message in our logs each time we upload
a photo. Find your terminal: let's watch the logs with:

```terminal
tail -f var/log/dev.log
```

That's the log file for the `dev` environment. I'll clear my screen, then move
over, select a photo and... move back. There it is:

> Important message! 🧀

I agree! That *is* important! This is cool... but not what we really want. What
we *really* want to do is use a worker to consume a message from a queue - probably
a JSON message - and *transform* that intelligently into a `LogEmoji` object so
Messenger can handle it. How do we do that? With a dedicated transport and
a customer serializer. Let's do that next!
