# Custom Transport Serializer

If an external system sends messages to a queue that we're going to read, those
messages will probably be sent as JSON or XML. We added a message formatted as
JSON. To read those, we set up a transport called `external_messages`. But when
we consumed that JSON message... it exploded! Why? Because the *default* serializer
for every transport is the `PhpSerializer`. Basically, it's trying to call
`unserialize()` on our JSON. That's...uh... not gonna work.

Nope, if you're consuming messages that came from an external system, you're
going to need a custom serializer for your transport. Creating a custom serializer
is... actually a *very* pleasant experience.

## Creating the Custom Serializer Class

Inside of our `src/Messenger/` directory... though this class could live anywhere..
let's create a new PHP class called `ExternalJsonMessengerSerializer`. The only
rule is that this needs to implement `SerializerInterface`. But, careful! There
are *two* `SerializerInterface`: one is from the Serializer component. We want the
*other* one: the one from the Messenger component. I'll go to the "Code Generate"
menu - or Command + N on a Mac - and select "Implement Methods" to add the two that
this interface requires: `decode()` and `encode()`.

[[[ code('dbcb2ac20c') ]]]

## The encode() Method

The idea is beautifully simple: when we *send* a message through a transport that
uses this serializer, the transport will call the `encode()` method and pass us
the `Envelope` object that contains the message. Our job is to turn that into
a string format that can be sent to the transport. Oh, well, notice that this returns
an *array*. But if you look at the `SerializerInterface`, this method should return
an array with two keys: `body` - the body of the message - and `headers` - any
headers that should be sent.

Nice, right? But... we're actually *never* going to *send* any messages through
our external transport... so we don't need this method. To prove that it will never
be called, throw a new `Exception` with:

> Transport & serializer not meant for sending messages

[[[ code('105fea8568') ]]]

That'll give me a gentle reminder in case I do something silly and route a message
to a transport that uses this serializer by accident.

## The decode() Method

The method that *we* need to focus on is `decode()`. When a worker consumes a
message from a transport, the transport calls `decode()` on its serializer. Our
job is to read the message from the queue and turn that into an `Envelope` object
with the *message* object inside. If you check out the `SerializerInterface` one
more time, you'll see that the argument we're passed - `$encodedEnvelope` - is
really just an array with the same two keys we saw a moment ago: `body`
and `headers`.

Let's separate the pieces first: `$body = $encodedEnvelope['body']` and
`$headers = $encodedEnvelope['headers']`. The `$body` will be the raw JSON in
the message. We'll talk about the headers later: it's empty right now.

[[[ code('edfb7589bd') ]]]

## Turning JSON into the Envelope

Ok, remember our goal here: to turn this JSON into a `LogEmoji` object and then
put that into an `Envelope` object. How? Let's keep it simple! Start with
`$data = json_decode($body, true)` to turn the JSON into an associative array.

[[[ code('f3d4436b47') ]]]

I'm not doing any error-checking yet... like to check that this is *valid* JSON -
we'll do that a bit later. Now say: `$message = new LogEmoji($data['emoji'])`
because `emoji` is the key in the JSON that we've decided will hold the `$emojiIndex`.

[[[ code('940d706826') ]]]

Finally, we need to return an `Envelope` object. Remember: an `Envelope` is
just a small wrapper *around* the message itself... and it might also hold some
stamps. At the bottom, return `new Envelope()` and put `$message` inside.

[[[ code('7c95c26179') ]]]

## Configuring the Serializer on the Transport

Done! We rock! This is already a *fully* functional serializer that can *read*
messages from a queue. But our transport won't just start "magically" using it:
we need to configure that. And.. we already know how! We learned earlier that
each transport can have a `serializer` option. Below the external transport, add
`serializer` and set this to the *id* of our service, which is the same as the
class name: `App\Messenger\`... and then I'll go copy the class name:
`ExternalJsonMessengerSerializer`.

[[[ code('5f97551e8f') ]]]

*This* is why we created a separate transport with a separate queue: we *only*
want the *external* messages to use our `ExternalJsonMessengerSerializer`. The other
two transports - `async` and `async_priority_high` - will still use the simpler
PhpSerializer... which is *perfect*.

Ok, let's try this! First, find an open terminal and tail the logs:

```terminal
tail -f var/log/dev.log
```

And I'll clear the screen. Then, in my other terminal, I'll consume messages
from the `external_messages` transport:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Perfect! There are no messages yet... so it's just waiting. But we're *hoping*
that when we publish this message to the queue, it will be consumed by our worker,
decoded correctly, and that an emoji will be logged! Ah, ok - let's try it. Publish!
Oh, then move back over to the terminal.... there it is! We got an important message:
cheese: it received the message and handled it down here.

So... we did it! We *rock*!

But... when we created the `Envelope`, we didn't put any stamps into it. Should
we have? Does a message that goes through the "normal" flow have some stamps on
it that we should manually add here? Let's dive into the workflow of a message
and its *stamps*, next.
