# Envelopes & Stamps

We just got a request from Ponka herself... and when it comes to this site, Ponka
is the boss. She thinks that, when a user uploads a photo, her image is actually
being added a little bit *too* quickly. She wants it to take longer: she wants it
to feel like she's doing some *really* epic work behind the scenes to get into your
photo.

I know, it's *kind* of a silly example - Ponka is so weird when you talk to her before
her shrimp breakfast and morning nap . But... it *is* an interesting challenge:
could we somehow not *only* say: "handle this later"... but also "wait at least
5 seconds before handling it?".

## Envelope: A Great Place to put a Message

Yep! And it touches on some super cool parts of the system called stamps and envelopes.
First, open up `ImagePostController` and go up to where we create the
`AddPonkaToImage` object. `AddPonkaToImage` is called the "message" - we know that.
What we *don't* know is that, when you pass your message to the bus, internally,
it gets wrapped inside something called an `Envelope`.

Now, this isn't an especially important detail except that, if you have an `Envelope`,
you can attach extra config to it via *stamps*. So yes, you literally put a message
in an envelope and then attach stamps. Is this your favorite component or what?

Anyways, those stamps can carry all sorts of info. For example, if you're using
RabbitMQ, you can configure a few things about how the message is *delivered*,
like something called a "routing key". Or, you can configure a delay.

## Put the Message into the Envelope, then add Stamps

Check this out: say `$envelope = new Envelope()` and pass it our `$message`. Then, 
pass this an optional second argument: an array of stamps. 

[[[ code('379ae29dbb') ]]]

Include just one: `new DelayStamp(5000)`. This indicates to the transport...
which is kind of like the mail carrier... that you'd like this message 
to be delayed 5 seconds before it's delivered. Finally, 
pass the `$envelope` - *not* the message - into `$messageBus->dispatch()`.

[[[ code('dc09d06bda') ]]]

Yep, the `dispatch()` method accepts raw message objects *or* `Envelope` objects.
If you pass a raw message, it wraps it in an `Envelope`. If you *do* pass an
`Envelope`, it uses it! The end result is the same as before... except that we're
now applying a `DelayStamp`.

Let's try it! This time we *don't* need to restart our worker because we haven't
changed any code *it* will use: we only changed code that controls how the
message will be *delivered*. But... if you're ever not sure - just restart it.

I *will* clear the console so we can watch what happens. Then... let's upload
three photos and... one, two, three, four there it is! It delayed 5 seconds
and *then* started processing each like normal. There's not a 5 second delay
*between* handling each message: it just makes sure that each message is handled
no *sooner* than 5 seconds after sending it.

Side note: In Symfony 4.3, the Redis transport doesn't support delays - but it
may be added in the future.

## What other Stamps are There?

Anyways, you may not use stamps a *ton*, but you will need them from time-to-time.
You'll probably Google "How do I configure validation groups in Messenger" and learn
*which* stamp controls this. Don't worry, I'll talk about validation later - it's
*not* something that's happening right now.

One *other* cool thing is that, internally, Messenger *itself* uses stamps to track
and help deliver messages correctly. Check this out: wrap `$messageBus->dispatch()`
in a `dump()` call.

[[[ code('4c348a2b31') ]]]

Let's go over and upload one new image. Then, on the web debug toolbar, find the
AJAX request that just finished - it'll be the bottom one - click to open its
profiler and then click "Debug" on the left. There it is! The `dispatch()` method
*returns* an `Envelope`... which holds the message of course... and *now* has *four*
stamps! It has the `DelayStamp` like we expected, but also a `BusNameStamp`, which
records the name of the bus that it was sent to. This is cool: we only have one
bus now, but you're allowed to have *multiple*, and we'll talk about why you might
do that later. The `BusNameStamp` helps the worker command know *which* bus to
send the `Envelope` to after it's read from the transport.

That `SentStamp` is basically a marker that says "this message was sent to
a transport instead of being handled immediately" and this `TransportMessageIdStamp`,
literally contains the *id* of the new row in the `messenger_messages` table...
in case that's useful.

You don't *really* need to care about any of this - but watching what stamps are
being added to your `Envelope` may help you debug an issue or do some more advanced
stuff. In fact, some of these will come in handy soon when we talk about middleware.

For now, remove the `dump()` and then, so I don't drive myself crazy with how slow
this is, change the `DelayStamp` to 500 milliseconds. Shh, don't tell Ponka. After
this change... yep! The message is handled *almost* immediately.

[[[ code('a8c5b2521d') ]]]

Next, let's talk about retries and what happens when things go wrong! No joke: this
stuff is *super* cool.
