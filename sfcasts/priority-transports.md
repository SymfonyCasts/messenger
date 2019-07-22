# High Priority Transports

The two messages that we we're sending to to our `async` transport are
`AddPonkaToImage` and `DeletePhotoFile`, which handles deleting the physical file
from the filesystem. And... that second one isn't something that user actually notices
or cares about - it's just house keeping. So, if it happened 5 minutes from now
or 10 days from now, they wouldn't care.

This creates an interesting situation. Our worker handles things in a
first-in-first-out basis: if we send 5 messages to the transport, the worker will
handle them in the order in which they were sent. This means that if a *bunch* of
images are deleted and then someone uploads a new photo, the worker will process
*all* of those delete messages *before* finally adding Ponka to the photo. And
that... isn't ideal.

Nope - the truth is that `AddPonkaToImage` messages should have a higher priority
in our system than `DeletePhotoFile`: we *always* want `AddPonkaToImage` messages
to be handled *before* any `DeletePhotoFile` messages that may be in the queue...
even if they were added first.

## Creating the "high" Priority Transport

So... can we set a priority on messages? Not exactly. It turns out that in the
queueing world, this is solved by creating *queues* and giving each of *those* a
priority. In Symfony Messenger, that translates to multiple *transports*.

It's just his simple: below the `async` transport, create a new transport called,
how about, `async_priority_high`. Let's use the same DSN as before, which in our
case is using `doctrine`. Below, add `options`, then set `queue_name` to `high`.
The name `high` isn't important here - we could use anything. Remember, the
`queue_name` option is specific to Doctrine. It's ultimately just a column in the
table and it operates like a category: we can create multiple "queues" of messages
inside the same table. And also, for *any* transport, you can configure these options
as query parameters on the DSN or under this `options` key.

At this point we have three queues, which are all stored in the same table in the
database, but with different `queue_name` values. And now that we have this new
transport, we can route `AddPonkaToImage` to `async_priority_high`.

## Consuming Prioritized Transports

If we stopped now... all we've *really* done is make it possible to send these
two different message classes to two different queues. But there's nothing special
about `async_priority_high`. Sure, I put the word "high" in its name, but it's no
more special than `async`.

The real magic comes from the worker. Find your terminal where the worker is running
and hit Control+C to stop it. If you *just* run `messenger:consume` without any
arguments and you have more than on transport, it asks you which transport you
want to consume:

```terminal
php bin/console messenger:consume
```

Meaning, which transport do you want to receive messages from. But actually, you
can read messages from *multiple* transports at once *and* tell the worker which
should be read first. Check this out: I'll say `async_priority_high, async`.

This tells the worker: first ask `async_priority_high` if it has any messages.
If it doesn't, *then* go check the `async` transport.

We should be able to see this in action. I'll refresh the page, delete a *bunch*
of photos here as fast as I can and then upload a couple of photos. Now check the
terminal output:

It's handling `DeletePhotoFile` then... now `AddPonkaToImage`, another
`AddPonkaToImage`, *another* `AddPonkaToImage` and... yea! Then it went back to
handling the lower-priority `DeletePhotoFile`.

So, in the beginning - before we uploaded - it *did* consume a few `DeletePhotoFile`
messages. But as soon as it saw a message on that `async_priority_high` transport,
it consumed all of those until it empty. When it was, it *then* returned consuming
messages from `async`.

Basically, each time the worker looks for the next message, it checks the highest
priority transport first and *only* asks the next transport - or transports - if
its empty.

And... that's it! Create a new transport for however many different priority
"levels" you need, then tell the worker command which order to process them.
Oh, and instead of using this interactive way of doing things, you can run:

```terminal
php bin/console messenger:consume async_priotity_high async
```

Perfect. Next, let's talk about *one* option we can use to make it easier to develop
while using queues... because *always* needing to remember to run the worker command
while coding *can* be a pain.
