# Graceful Failure in the Transport Serializer

Our shiny new `external_messages` transport reads messages from this
`messages_from_external` queue, which we're *pretending* is being populated by
an external application. We're taking this JSON and, in
`ExternalJsonMessengerSerializer`, decoding it, creating the `LogEmoji` object,
putting it into an `Envelope`, even adding a *stamp* to it, and ultimately
returning it, so that it can *then* be dispatched back through the message bus system.

## Failing on Invalid JSON

This is looking great! But there are two improvements I want to make. First, we
haven't been coding very defensively. For example, what if, for some reason,
the message contains invalid JSON? Let's check for that: if `null === $data`, then
throw a `new MessageDecodingFailedException('Invalid JSON')`

[[[ code('3e129a0740') ]]]

I'll show you why we're using this *exact* exception class in a minute. But let's
try this with some invalid JSON and... see what happens. Go restart the worker
so it sees our new code:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Then, in the RabbitMQ manager, let's make a *very* annoying JSON mistake: add a
comma after the last property. Publish that message! Ok, move over and... explosion!

> MessageDecodingFailedException: Invalid JSON

Oh, and interesting: this *killed* our worker process! Yep, if an error happens
during the *decoding* process, the exception *does* kill your worker. That's not
ideal... but in reality... it's not a problem. On production, you'll already be
using something like supervisor that will *restart* the process when it dies.

## Failing on Missing JSON Field

Let's add code to check for a *different* possible problem: let's check to
see if this `emoji` key is missing: if not `isset($data['emoji'])`, this time
throw a *normal* exception: `throw new \Exception('Missing the emoji key!')`.

[[[ code('4266ab41a8') ]]]

Ok, move over and restart the worker:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Back in Rabbit, remove the extra comma and change `emoji` to `emojis`. Publish!
Over in the terminal... great! It exploded! And other than the exception
*class*... it looks *identical* to the failure we saw before:

> Exception: Missing the emoji key!

But... something different *did* just happen. Try running the worker again:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Woh! It exploded! Missing the emoji key. Run it again:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

## The Magic of MessageDecodingFailedException

The same error! *This* is the difference between throwing a normal
`Exception` in the serializer versus the special `MessageDecodingFailedException`.
When you throw a `MessageDecodingFailedException`, your serializer is basically
saying:

> Hey! Something went wrong... and I *do* want to throw an exception. *But*,
> I think we should *discard* this message from the queue: there is no point
> to trying it over and over again. kthxbai!

And that's *super* important. If we don't discard this message, each time our
worker restarts, it will fail on that *same* message... over-and-over again...
forever. Any *new* messages will start piling up *behind* it in the queue.

So let's change the `Exception` to `MessageDecodingFailedException`. Try it now:

[[[ code('f339b1ef8b') ]]]

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

It will explode the first time... but the `MessageDecodingFailedException` *should*
have removed it from the queue. When we run the worker now:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Yep! The message is gone and the queue is empty.

Next, let's add *one* more superpower to this serializer. What if that outside
system actually sends our app *many* different types of message - not only a
message to log emojis, but maybe also messages to delete photos or cook some
pizza! How can our serializer figure out which messages are which... and which
message *object* to create?
