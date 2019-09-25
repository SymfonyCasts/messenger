# JSON, Message Headers & Serializer Options

But in addition to the payload, each message can also have "headers". Check that
key out on our message. Woh! This contains a big JSON structure of the original
class name *and* the data and class names of the *stamps* attached to the message!

Why did Messenger do this? Well, find your terminal and consume the `async` transport:

```terminal
php bin/console messenger:consume -vv async
```

This *still* works. Internally, the Symfony serializer used the info on the
`headers` to figure out how to take this JSON and turn it into the correct
object. It used the `type` header to know that the JSON should become an
`ImagePostDeletedEvent` object and then looped over the stamps and turned each
of *those* back into stamp objects for the envelope.

The *really* nice thing about using the Symfony serializer in Messenger is that
the `payload` is this simple, pure JSON structure that could be consumed by
any application in any language. It *does* contain some PHP class info on the
*headers*, but another app can just ignore that. But thanks to those headers,
if the same app *does* both send and consume a message, the Symfony serializer
can still be used.

But wait... if that's true - if the Symfony serializer creates messages that
can be consumed by external systems *or* by our same app - then why isn't it
the default serializer in Messenger? An *excellent* question! The reason is
that the Symfony serializer requires your classes to follow a few *rules* in
order to be serialized and unserialized correctly. If your class doesn't follow
those rules, you could end up with a property that was set on the original object,
but is now null when reading it from the transport. In other words, the PHP serializer
is *much* easier and more dependable when everything is done by the same app.

Well, the reason is that the Symfony serializer a requires your classes
to be written in a certain way. Uh, that doesn't always make it convenience to be
used. So, um,

so if you are sending in consuming from your application, use the PHP serializer
cause it just makes your life a lot easier. But if you're sending it to another one,
you can do this.

Now additionally, if you are using the serializer, you can run

```terminal
php bin/console config:sump framework messenger
```

and discover a couple of other options under the
Symfony, uh, framework messengers serializer Symfony serializer thing. Here's where
you can from a format that you want JSON Our XML. And then there's a key here or
called context which use the pass options to the serializer. Um,

So that's it. If you're sending to another system, you'll probably don't want to use
the Symfony serializer so that you can send things via JSON or you can create your
own custom serializer, um, and do whatever you want.

But for now, let's remove this. Your are key. Next we're going to look at the other
side of the equation. What if something external actually sends messages to the
queue, not our Symfony app. And we want our Symfony app to consume them.
