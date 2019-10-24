# Mapping Messages to Classes in a Transport Serializer

We've written our transport serializer to *always* expect only *one* type of message
to be put into the queue: a message that tells our app to "log an emoji". Your
app *might* be that simple, but it's more likely that this "external" system might
send 5 or 10 different *types* of messages. In that case, our serializer needs to
detect which *type* of message this is and then turn it into the correct
message *object*.

How can we do that? How can we figure out which *one* type of message this is?
Do we... just look at what fields the JSON has? We *could*... but we can also
do something smarter.

## Refactoring to a switch

Let's start by reorganizing this class a bit. Select the code at the bottom of
this method - the stuff related to the `LogEmoji` object - and then go to the
Refactor -> "Refactor This" menu, which is Ctrl+T on a Mac. Refactor this code
to a method called `createLogEmojiEnvelope`.

[[[ code('6d7aa90802') ]]]

Cool! That created a private function down here with that code. I'll add an
`array` type-hint. Back in `decode()`, we're already calling this method. So, no
big change.

[[[ code('77b1b16602') ]]]

## Using Headers for the Type

The *key* question is: if multiple *types* of messages are being added to the
queue, how can the serializer determine which *type* of message this is? Well,
we could add maybe a `type` key to the JSON itself. That might be fine. But, there's
*another* spot on the message that can hold data: the *headers*. These work a lot
like HTTP headers: they're just "extra" information you can store about the message.
Whatever header we put here will make it *back* to our serializer when it's consumed.

Ok, so let's add a new header called `type` set to `emoji`. I just made that up.
I'm not making this a class name... because that external system won't know or care
about what PHP classes we use internally to handle this. It's just saying:

> This is an "emoji" type of message

Back in our serializer, let's first check to make sure that header is set: if
not `isset($headers['type'])`, then throw a new `MessageDecodingFailedException`
with:

> Missing "type" header

[[[ code('851d7d57c9') ]]]

Then, down here, we'll use a good, old-fashioned switch case statement on
`$headers['type']`. If this is set to `emoji`, return
`$this->createLogEmojiEnvelope()`.

[[[ code('8bba5f6b42') ]]]

After this, you would add any other "types" that the external system publishes,
like `delete_photo`. In those cases you would instantiate a *different* message
object and return that. And, if some unexpected "type" is passed, let's throw a
new `MessageDecodingFailedException` with

> Invalid type "%s"

passing `$headers['type']` as the wildcard.

[[[ code('7e80acceae') ]]]

Kinda cool, right? Let's go stop our worker, then restart it so it sees our new code:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Back in the Rabbit manager, I'll change the `emojis` key back to `emoji` and...
publish! In the terminal... sweet! It worked! Now change the `type` header
to something we don't support, like `photo`. Publish and... yea! An exception
killed our worker:

> Invalid type "photo".

Ok friends... yea... that's it! Congrats on making it to the end! I hope
you enjoyed the ride as much as I did! I mean, handling messages asynchronously...
that's pretty fun stuff. The *great* thing about Messenger is that it works brilliantly
out of the box with a *single* message bus and the Doctrine transport. Or, you
can go *crazy*: create multiple transports, send things to RabbitMQ, create custom
exchanges with binding keys or use your own serializer to... well... basically
do *whatever* you want. The power... it's... intoxicating!

So, start writing some crazy handler code and then... handle that work later! And
let us know what you're building. As always, if you have some questions, we're there
for you in the comments.

Alright friends, seeya next time!
