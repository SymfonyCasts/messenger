# JSON, Message Headers & Serializer Options

In addition to the payload, a message in RabbitMQ can also have "headers". Check
that key out on our message. Woh! This contains a big JSON structure of the original
class name *and* the data and class names of the *stamps* attached to the message!

Why did Messenger do this? Well, find your terminal and consume the `async` transport:

```terminal
php bin/console messenger:consume -vv async
```

This *still* works. Internally, the Symfony serializer uses the info on the
`headers` to figure out how to take this simple JSON string and turn it into the
correct object. It used the `type` header to know that the JSON should become an
`ImagePostDeletedEvent` object and then looped over the stamps and turned each
of *those* back into a stamp object for the envelope.

The *really* nice thing about using the Symfony serializer in Messenger is that
the `payload` is this simple, pure JSON structure that can be consumed by
any application in any language. It *does* contain some PHP class info on the
*headers*, but another app can just ignore that. But *thanks* to those headers,
if the same app *does* both send and consume a message, the Symfony serializer
can still be used.

## Shouldn't we Always use the Symfony Serializer?

But wait... if that's true - if the Symfony serializer creates messages that
can be consumed by external systems *or* by our same app - then why isn't it
the default serializer in Messenger? An *excellent* question! The reason is
that the Symfony serializer requires your classes to follow a few *rules* in
order to be serialized and unserialized correctly - like each property needs a
setter method or a constructor argument where the name matches the property name.
If your class doesn't follow those rules, you can end up with a property that is
set on the original object, but suddenly becomes null when it's read from the
transport. No fun.

In other words, the PHP serializer is easier and more dependable when everything
is done by the same app.

## Configuring the Symfony Serializer

Anyways, if you *are* using the Symfony serializer, there are also a few things
that can be configured. Find your terminal and run:

```terminal
php bin/console config:dump framework messenger
```

Check out that `symfony_serializer` key. *This* is where you configure the behavior
of the serializer: the format - `json`, `xml` or something else, and the
`context`, which is an array of options for the serializer.

Of course, you can *also* create a totally *custom* serializer service. And if
you have the *opposite* workflow to what we just described - one where your
app *consumes* messages that were sent to Rabbit from some *other* system - a custom
serializer is *exactly* what you need. Let's talk about that next.
