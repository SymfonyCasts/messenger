# External Transport

Coming soon...

What if some external system was the actually sending messages to one of our queues and rabbit
MQ that was not our Symfony app, but we wanted our Symfony app to consume those
messages. How would that work? Like for example, some external system, I dunno, I
need to think of an example here. Um, maybe a user's able to delete one of their
photos via some totally an external system and then that needs to be communicated
back to our systems that we can delete the file. How does that work? Well, each
transport in messenger actually has two jobs to send messages to the transport and
then also to consume them via the worker command. It actually, you don't have to use
both of them as we just talked about a second ago. You could just choose to send
messages to the transport and then never actually consumed them. But you can also do
the opposite. You could never send an only consume messages

so that's exactly what we're going to do. We're going to manually send some messages
into a queue and then configure a new transport that is going to read those messages.
And then handle them through messenger. And the way that we do that is actually going
to feel almost exactly like everything that we've done so far. So instead of
overexplaining it, let's see it in action. I want to pretend like we're able to, I to
pretend like an external system is going to send us a message that says I need you to
log and Emoji. So what we're gonna do is we're gonna read information about what
Emoji they want logged, and then we're going to log that Emoji. Now normally when we
work with messenger, we always create a message class and then we create a message
handler.

In this case, we're going to do exactly the same thing in the `Command/` directory.
Let's create a new PHP class called `LogEmoji`. And we're going to pretend that at a
`public function __construct()`. And we're gonna pretend that when the external
system sends us the message, they're going to give us an index and integer index of
which Emoji they want. And you know, w we're going to have a list of about five
emojis and they're gonna be able to pick from zero to five to tell us which of those
emojis that we want. So we'll add the `$emojiIndex` argument on and I'll go 
Alt + Enter and select Initialize Fields to create that property and set it. And then
we're gonna need a getter of course for this. So I'll go to Code -> Generate menu or
Command + N on a Mac, go to getters and generate that `getEmojiIndex()`. Perfect. So
that's a perfectly boring, normal, uh, message class. Step two in the 
`MessageHandler/Command/` directory, that's create a new `LogEmojiHandler` class. 
This will implement our normal `MessageHandlerInterface` with the 
`public function __invoke()` with the type it for the message `LogEmoji $logEmoji`.

Perfect.

now we'll just fill in the logic. I'm going to paste in some emojis on top. Those are
our five emojis that we're gonna use. Cookie dinosaur cheese, dinette robot, and of
course poop. And then because we're going to be logging something inside of here,
I'll add a `__constructor()` with our `LagerInterface` type hint, and then hit Alt + Enter
Initialize fields to create that property and set it. Then down here in our logic,
it's pretty simple. I'll say `$index = $logEmoji->getEmojiIndex()`, then 
`$emoji = self::emojis` to reference that static property, `[$index]` 
`?? self::emojis[0]`. So basically it's a way of saying if the
index does exist inside of self emojis use it. Otherwise, if it doesn't exist, then
we'll just always use the cookie by default because everyone loves cookies. Then
we'll say `$this->logger->info('Important message! ')`and then we will add the `$emoji`
right there. So so far this is a perfectly functional message command and command
handler class and you can't tell at all that we're gonna do anything special
whatsoever. In fact, let's go up to our `ImagePostsController`, find the `create()`
function. This is the end point for we upload photos and just to see if this is
working. Let's say `$messageBus->dispatch(new LogEmoji(2))`. And we'll just pass
it the index to,

okay.

So if this is all working, then when we upload a photo, we should see a log entry
added. So let's actually go over and let's 

```terminal
tail -f var/log/dev.log
```

That's the log file for the Dev environment. Clear my screen. Then we'll move over, select a
photo and there it is.
 
> Important message! 🧀

So that ran synchronously and it worked just like we expect.

All right. So that's not really what we want. We want to do as effectively this same
thing except that we want to, um, except that the goal is that we're going to consume
from a transport. The goal is that we're going to consume from some transport. I want
that transport, it's going to read from Rabbit MQ. We'll take that data from
rabbit MQ and we'll turn it into a `LogEmoji` object and effectively dispatch it come
out that message. Bus Era dispatch right now. So the next step in getting this set up
is to create a, another transport. So I want to keep my `async` transport cause that's
going to be sending messages. And receiving messages. My `async_priority_high`, I'm
going to be sending and receiving, but I also want to create a new transport down
here. And the whole point of this transport is we're not going to send messages to
this transport. We're only going to consume messages from it. So I'm going to call it
`external_messages`. I'll use the same DSN because we're gonna still be consuming
things from rabbit MQ. Then I'll add options below here. Now the idea is instead of
consuming messages from `messages_high` are `messages_normal`, we're going to consume
them from a new uh, queue inside of here.

so I'm going to add queues

and then `messages_from_externals`, what we'll call it, a till that. Then above this, I
actually want you to add `auto_setup: false`. So there's like a few important things
happen here. The first thing is having this queue set up here says that when we
consume from this external messages, transport, it's gonna try to read messages from
a queue called `messages_from_external`. The second important thing here is I have 
`auto_setup: false`, which probably makes sense because since we're expecting some external
system to send messages to this queue, we probably don't want or need messenger to
automatically create this queue for us. We're going to expect that it's already going to be
there. Also, you'll notice I don't have any exchange information down here. That's
also on purpose. The exchange is only needed when you're sending messages to a
transport. So the fact that we're never going to send a message to this transport
means that we do not need any exchange information. Now in reality, the exchange, uh,
is

no, I'm not going to cover that.

So with just this, we should be able to consume from this new transport. So spin over
and run

```terminal
php bin/console messenger:consume -vv external_messages
```

and it explodes. This is awesome. 

> Server channel error: 404, message: NOT_FOUND - no queue 'messages_from_external'

So because we have the `auto_setup: false` instead of
creating that queue, it's just airing out on it. So let's go ahead over here and
let's create that by hand. So we'll call external messages. I'm not going to worry
too much about the arguments, but we will make it durable. Then hit add Q.

Now if we move over and run it again, oh, it's still air it out. Oopsies so scroll
up, you can see messages from external.

So move over. I'll copy that messages from external queue and then we'll go create that
by hand. Call it `messages_from_external`. Uh, won't worry too much about the options,
but we will make it durable. And there we go. We now have a queue. So if we spin back
over now and try it, 

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

it works. It's consuming messages from that queue. Of course,
there's nothing in that queue. Um, but uh, but it's a reading from it. Alright, so
what are the messages going to look like? How can we kind of fake this? So here's the
idea. I'm going to pretend you can actually go into the rabbit MQ management and you
can use it to publish a message. So we can do this to kind of, um, pretend that we
are, uh, sending from an external system. So the key thing here is the payload.
That's the data that we're actually going to sentence the data that's gonna be
received. So what would it look like for an outside system to send a message to us
that says, we want to log in Emoji? Well, it might, let's just make something up. It
might look, we might decide, I want it looks like this. Uh, it's some JSON with an
Emoji key and they're gonna send us two. That'll be the index that they want us to
log. Cool. So let's publish this message.

And immediately if you look, we've over, our worker has exploded. Check this out,

> Could not decode message using PHP serialization:

And then it shows our JSON. So the
first big thing with an, if you're consuming messages that are coming from a third
party is they're probably not sending them in a PHP serialized format. In fact, they
shouldn't be. They're probably sending them as JSON or XML. So our transport is going
to, so our transport is going to need a custom serializer that's able to take this
JSON and turn it into that object. Let's do that next.