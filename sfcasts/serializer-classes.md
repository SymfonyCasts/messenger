# Serializer Classes

Coming soon...

Thanks to our new `external_messages` transport. It reads messages from this queue
that's being populated by some external application. We're taking this JSON and our
`ExternalJsonMessengerSerializer` decoding that, creating the `LogEmoji` object, putting it
into an envelope, even adding a stamp and ultimately returning it so that it can then
be, uh, uh, synth back through the message bus system. This is looking great, but
there are two improvements that I want to make first there. It's first, this could be
a little air prone. We're not coding very defensively. For example, like if invalid,
JSON said, let's check for that. If `null === $data`, then we'll say new message. We'll say
throw `new MessageDecodingFailedException()` 

> Invalid JSON 

I'm going to show you why we're using this exact exception class in a second.
But first let's try this and see what happens. So go over here and let's restart our
workers so it sees their new code.

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Then we'll go over and we'll do the very annoying thing where we add a comment to
JSON. Publish that message.

Move over and exploded. Yep. Message Decoding Failed Invalid JSON now does notice
one thing this actually killed our worker worker process stopped. So if you have a
decoding failure, it actually kills your worker. That's not as big of a problem as
you might think because on production your worker commands are going to need to run.
Um, you're going to need some process like supervisor anyways to make sure that
whenever your worker is killed, it's really started. So it's actually not that big of
a deal. Uh, it's not a big deal when your worker is killed like this. So let's code
something else defensively. Let's say, let's check for this `emoji` key. You know what,
if we have a Typo in the Emoji keys missing, so if not `isset($data['emoji'])` this time
that's actually throwing normal exception. Throw a `new \Exception()`. 

> Missing the emoji key!

All right, go over. Let's restart our worker. 

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Good. I'll take off my extra comma. And
this time I'll say `emojis: 4` publish and cool. It exploded and the explosion looks
almost the same as before. Just as different. It says Exception, Missing the emoji key!
But this time tried to check this out. Try running it again. 

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

It explodes. Missing the Emoji key. Run it again. 

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

It explodes. Missing the Emoji key. This is the
difference between throwing just a normal `Exception` inside of here versus this
special `MessageDecodingFailedException`. When you throw a `MessageDecodingFailedException`
it tells the, it says, I want to throw an exception, but I want to discard
this message from the queue, which is important because if you don't discard message
when your worker restarts, it's going to forever and ever and ever just choke on this
same one message. So let's just change this to `MessageDecodingFailedException`. Now
let me try it. It's going to explode the first time, but that now the 
`MessageDecodingFailedException` removed it from the queue. So when you run it now it's just
sitting there cause the queue is actually empty.

All right. The other one thing I want to talk about is that right now our transport
can only really handle this one type of message and that's because our serializers
only set our serializer. I'm only creates a `LogEmoji`. A more realistic system might
be one where an external system sends you maybe five or 10 different types of
messages and you might need to detect which type of message this is and then turn
this into one of 10 different classes. So how can we do that? How can we figure out
which type of messages being sent externally from us and turn that into different
classes? Well, we can do this entirely in the serializer ends up being a very, very
nice solution. First thing I'm gonna do,

is select kind of the bottom of this method here. I'm going to go to Control + T,
that's the refactor, refactor this menu and refactor this down to a method. I'm gonna
call that method. `createLogEmojiEnvelope`.

Cool. So that just creates a private function down here and I'll add an `array` type
hint

to that. And then we're calling it from up here. So nobody changed there. So one of
the ways for an external system to tell our application, what type of messages is, is
to add some sort of type key. And we could add like a type key to the JSON, but we
also have this headers up here which works a lot like http headers. We can actually
just add any headers we want and they're going to be communicated back to us via the
headers key here. So let's add a new header called `emoji`. I just made that up. Notice
we're not putting the class name or anything. We're just kind of putting a generic
string that is understood as one. We're sending a uh, an Emoji type of a message.
We'll put an Emoji type here. Now over here we can just check for that. So for
example, first of all code defensively, if not `isset($headers['type'])` well that's there
a new `MessageDecodingFailedException` and say 

> Missing "type" header 

down here we'll do
a good old fashioned switch case statement on headers type. We'll say that if the
case is `emoji`, because this is going to be a string that you are going to decide with
that external system beforehand,

then we will return `$this->createLogEmojiEnvelope()`. And down here you might have
something else, like a delete photo and you'd have some other function that you call
whatever other types of messages that you actually want. I'll come with those off for
now. And then if you don't hit any of the cases, just to be extra safe down here or
throw a new `MessageDecodingFailedException` and we'll pass 

> Invalid type "%s"

we'll pass it the `$headers['type']` key.

cool. So now if I go over here, I'll change this emojis back to `"emoji": 4`, and then we
have type Emoji Sophie publish that. Yes it worked. And we can see that over here in
our logs. And if we somehow change this type to something else like `photo` and publish
that.

then you're going to see that it still works out. Of course. Cause I need to restart
my worker. 

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

So let me try that again though. Actually I'll try the bad case first if I
posted that type of photo. There we go. We get the Invalid type "photo". Let's change
that back to Emoji. How much that message and

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

yes, this time it works. Alright, that's it.