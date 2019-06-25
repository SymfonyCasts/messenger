# Stamps Envelopes

Coming soon...

We just got a request from Ponka herself and when it comes to this site, punk gut,
his boss, she thinks that when we upload a new photo, her image is actually being
added to the image a little bit too quickly. She wants it to make, she wants it to
feel like, like there's some bigger work going on behind the scenes. She wants to
actually to take a little bit longer. She wants us to delay before we actually add
her image. So that's what we're going to do and it might sound a little silly to like
make your system go slower on purpose. Um, but there are use cases for this and it's
going to touch on a really important part of the system called stamps and envelopes.
So first open up the controller `ImagePostController` and go up to where we uh,
create the `AddPonkaToImage` and dispatch it into the message bus. `AddPonkaToImage`
is called our message.

Now internally, when you pass a message to, uh, the message bus, it's wrapped inside
of something called an envelope. And you can really imagine a message being put into
an envelope. Now that's not a really important detail except that one of the
superpowers of the envelope is that you can add extra information to it called
stamps. So yes, literally you a message in an envelope and you can attach a stamps to
it in those stance can configure all kinds of things like, um, like transport
specific options. Like you're using Rabbit Mq, you can pass it like routing keys, um,
or how long you want your transport to delay before handling a message. So check this
out.

We're going to say `$envelope = new Envelope()` and pass it our `$message`. Ben, I'm gonna
pass this a second optional argument, which is an array of stamps to put on there.
And here I'm going to say `new DelayStamp()` and I'm past this `5000`, which is at five
seconds. And then I'm going to pass the `$envelope`, not the message into 
`$messageBus->dispatch()`. And like I said a second ago, when you pass a message to dispatch, it
actually just creates an empty envelope for you. And, and for wraps your message in
it. If you asked an envelope, then that's the envelope that's used. So this is really
no different than what happened before except that we're applying a `DelayStamp` to
our message. All right, so let's restart Ms. Case. We actually don't even need to
restart our worker because this code is actually not handled by our worker. Let me
just go over and look at my work. I'm going to clear the screen here. Let's go over
and let's upload three photos

and then real quick, I'm gonna move over here and we'll count one, two, three, four,
five, yup. And you can see it and delayed and then immediately handled those
messages. So yeah, you're not going to use stamps a ton, but there's gonna be various
times in the system when you need to configure something about the delivery or
handling of your message in stamps are going to be the way that you do at. You seem
to understand that, that they're actually even more stamps are added internally to
track different things. So check this out. I'm going to wrap `$messageBus->dispatch()`
in a `dump()` call.

Then let's go over and I'll just upload one new image. And then down here I'm going
to open up the uh, profiler for that latest API request and then go down here to
debug. There it is. So you can see our envelope being dumped. You can see the ad punk
and an image inside of it, but check out the steps. There's four stamps inside of
there. There's our `DelayStamp` like we expected, but there's also a `BusNameStamp`.
This records the name of the bus that it was, uh, the message was, um, put onto you.
As we're going to talk about later, you can actually have multiple message buses and
this is so that when the worker processes your message, it knows which buses should
be handling it. Now says a couple things. The `SentStamp` actually basically says this
message has been sent to a transport and records, which one? And also something
called a tr, a `TransportMessageIdStamp`, which is a fancy way of the doctor and
transport saying, hey, this was the idea in the database of this is 62. These are all
things that you don't need to worry about, but there are things that are tracked
internally and if you ever need to do something a little bit more advanced, you might
run into these, uh, for example, in a few minutes we're going to create something
called a middleware.

Which allows you to hook into the process of handling SSmessages and the way they were
going to determine whether a message is being handled currently or instead was sent
to a transport we handle later is by looking for the `SentStamp` on the
envelope. So now has, has close that, let's remove our `dump()` and then so that I don't
drive myself go too crazy. Let's actually re genes that delay stamp down to just `500`
milliseconds. And when we do it this time, you can see that that message was handled
almost immediately.
