# Mapping Messages to Classes in a Transport Serializer

Coming soon...

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
