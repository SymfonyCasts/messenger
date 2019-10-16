# Serializing Messages as JSON

Once you start using RabbitMQ, a totally different workflow becomes possible...
a workflow that's *especially* common with bigger systems. The idea is that the
code that *sends* a message might *not* be the same code that consumes and
*handles* that message. Our app is responsible for both sending the messages to
RabbitMQ *and*, over here in the terminal, for *consuming* messages from the
queue and handling them.

But what if we wanted to send one or more messages to RabbitMQ with the expectation
that some *other* system - maybe some code written in a different language and
deployed to a different server - will consume and handle it? How can we do that?

Well... on a high level... it's easy! If we wanted to send things to this `async`
transport... but didn't plan to *consume* those messages, we wouldn't need to
change anything in our code! Nope, we just... wouldn't consume messages from that
transport when using the `messenger:consume` command. We could still consume
messages from *other* transports - we just wouldn't read these ones... because
we know someone else will. Done! Victory! Coffee!

## How are our Messages Formatted?

But... if you *were* going to send data to another system, how would you normally
format that data? Well, to use a more familiar example, when you send data to
an API endpoint, you typically format that data as JSON... or maybe XML. The
same is true in the queueing world. You can send a message to RabbitMQ in
*any* format... as long as whoever is consuming that message *understands* the
format. So... what format are we using now? Let's find out!

I'll go into the `messages_normal` queue... and just to be safe, let's empty this.
Messages sent to the `async` transport will eventually end up in this queue...
and the `ImagePostDeleteEvent` classes route there. Ok, back on our app, delete a
photo then, looking at our queue, in a moment... there it is! Our queue contains
the one new message.

Let's see *exactly* what this message looks like. Down below, there's a spot to
fetch a message out. But... for some reason... this hasn't been working for me.
To hack around this, I'll bring up my network tools, click "Get Message(s)" again...
and look at the AJAX request this just made. Open up the returned data and hover
over that `payload` property.

Yep, *this* is what our message looks like in the queue - this is the *body*
of the message. What *is* that ugly format? It's a serialized PHP object!
When Messenger consume this, it knows to use the `unserialize` function to get
it back into an object... and so, this format works awesome!

But if we expect a *different* PHP application to consume this... unserializing
it won't work because these classes probably won't exist. And if the code that
will handle this is written in a different language, pfff, they won't even have
a *chance* at reading and understanding this PHP-specific format.

The point is: using PHP serialization works *great* when the app that sends the
message also handles it. But it works *horribly* when that's not the case. Instead,
you'll probably want to use JSON or XML.

## Using the Symfony Serializer

Fortunately, using a different format is easy. I'll purge that message out of
the queue one more time. Move over and open `config/packages/messenger.yaml`.
One of the keys that you're allowed to have below each transport is called
`serializer`. Set this to a special string: `messenger.transport.symfony_serializer`.

[[[ code('627715a70b') ]]]

When a message is sent to a transport - whether that's Doctrine, AMQP or something
else - it uses a "serializer" to *encode* that message into a string format
that can be sent. Later, when it *reads* a message from a transport, it uses that
same serializer to *decode* the data back into the message object.

Messenger comes with two "serializers" out-of-the-box. The first one is the PHP
serializer... which is the default. The second is the "Symfony Serializer",
which uses Symfony's Serializer component. *That* is the serializer service that we
just switched to. If you don't already have the serializer component installed,
make sure you install it with:

```terminal
composer require serializer
```

The Symfony serializer is great because it's *really* good at turning objects
into JSON or XML, and it uses JSON by default. So... let's see what happens!
Move back and delete another photo. Back in the Rabbit manager, I'll use the
same trick as before to see what that message looks like.

Woh. This is *fascinating*! The `payload` is now... *super* simple: just a `filename`
key set to the filename. This is the JSON representation of the message class,
which is `ImagePostDeletedEvent`. Open that up:
`src/Message/Event/ImagePostDeletedEvent.php`. Yep! The Symfony serializer turned
this object's *one* property into JSON.

We're not going to go *too* deep into Symfony's serializer component, but if you
want to know more, we go *much* deeper in our
[API Platform Tutorial](https://symfonycasts.com/screencast/api-platform).

Anyways, this simple JSON structure *is* something *any* other system could
understand. So... we rock!

But... just as a challenge... if we *did* try to *consume* this message from
our Symfony app... would it work? I'm not sure. If this message is consumed,
how would the serializer know that this simple JSON string needs to decoded into
an `ImagePostDeletedEvent` object? The answer... lies somewhere else in the message:
the headers. That's next.
