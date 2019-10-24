# Transport for Consuming External Messages

We've just created a new message class & handler... then instantiated it and dispatched
it directly into the message bus. Yep, we just did something totally... boring!
But... it's actually pretty similar to our *real* goal! Our *real* goal is to
pretend that an *outside* system is putting messages into a RabbitMQ queue...
probably formatted as JSON... and *we* will read those messages, transform that
JSON into a `LogEmoji` object and... basically dispatch that through the message
bus. It's really the same basic flow: in both cases, we create a `LogEmoji`
object and pass it to Messenger.

[[[ code('c0f66b6018') ]]]

## Creating a Dedicated Transport

The first step is to create a transport that will read these messages from whatever
queue the outside system is placing them into. We'll keep the `async` and
`async_priority_high` transports: we'll continue to send and receive from those.
But now create a new one called, how about: `external_messages`. I'll use the same
DSN because we're *still* consuming things from RabbitMQ. But for the options,
instead of consuming messages from `message_high` or `messages_normal`, we'll
consume them from whatever queue that outside system is using - let's pretend
it's called `messages_from_external`. Set that to just `~`.

[[[ code('d277b49b2c') ]]]

By the way, it *is* important that we use a *different* transport that reads from
a *different* queue for these external messages. Why? Because, as you'll see in
a few minutes, these external messages will need special logic to decode them
back into the correct object. We'll attach that special logic to the transport.

Anyways, above this add `auto_setup: false`.

[[[ code('f12cb59a03') ]]]

Ok, there are a few important things happening here. The first is that this
queue config means that when we *consume* from the `external_messages` transport,
Messenger will read messages from a queue called `messages_from_external`. The
second important thing is `auto_setup: false`. This tells Messenger *not* to
try to create this queue. Why? Well... I guess our app *could* create that queue...
that would probably be fine... but since we're expecting an external system to
send messages to this queue, I'm guessing that *that* system will want to be
responsible for making sure it exists.

Oh, and you probably also noticed that I didn't add any `exchange` config. That
was on purpose. An exchange is only used when *sending* a message. And because
we're not planning on *ever* sending a message through this transport, that part
of the transport just won't ever be used.

So with *just* this, we should be able to consume from the new transport. Spin over
to your terminal and run:

```terminal
php bin/console messenger:consume -vv external_messages
```

And... it explodes! This is awesome.

> Server channel error: 404, message: NOT_FOUND - no queue 'messages_from_external'

We're seeing our `auto_setup: false` in action! Instead of creating that queue
when it didn't exist, it exploded. Love it!

## Creating the Queue By Hand

So now, let's pretend that *we* are that "external" system and *we* want to create
that queue. Copy the queue name - `messages_from_external` - and, inside the Rabbit
Manager, create a new queue with that name. Don't worry about the options - they
won't matter for us.

And... hello queue! Let's go see if we can consume messages from it:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

It works! Well... there aren't any *messages* in the queue yet, but it's happily
checking for them.

## Putting an "External" Message into the queue

*Now*, let's *continue* to pretend like *we* are the "external" system that will
be sending messages to this queue. On the queue management screen, we can publish
a message into the queue. Convenient!

So... what will these messages look like? Well... they can *look* like anything:
JSON, XML, a binary image, ASCII art - whatever we want. We'll just need to make
sure that our Symfony app can *understand* the message - that's something we'll
work on in a few minutes.

Let's think: if an outside system wants to send our app a *command* to log an emoji...
and it can choose *which* emoji via a number... then... maybe the message is
JSON that looks like this? An `emoji` key set to 2:

```json
{
  "emoji": 2
}
```

Publish! Ok, go check the worker! Woh... it exploded! Cool!

> Could not decode message using PHP serialization

And then it shows our JSON. Of course! If you're consuming a message that was
placed in the queue by an external system... that message *probably* won't be
in the PHP serialized format... and it really *shouldn't* be. Nope, the message
will probably be JSON or XML. The problem is that our transport is trying to
transform that JSON into an object by using the default PHP serializer. Literally,
it's calling `unserialize()` on that JSON.

We need to be smarter: when a transport consumes messages from an external system,
it needs to have a *custom* serializer so we can take control. Let's do that next.
