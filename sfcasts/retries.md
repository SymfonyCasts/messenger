# Retrying on Failure

When you start handling things asynchronously, thinking about what happens when
code fails is even *more* important! Why? Well, when you handle things synchronously,
if something fails, typically, the *whole* process fails, not just half of it.
Or, at least, you *can* make the whole process fail if you need to.

For example: pretend all our code is still synchronous: we save the `ImagePost`
to the database, but then, down here, adding Ponka to the image fails... because
she's napping. Right now, that *would* result in *half* of the work being done...
which, depending on how sensitive your app is, may or may not be a huge deal. If
it *is*, you can solve it by wrapping all of this in a database transaction.

Thinking about *how* things will fail - and coding defensively when you need to -
is just a healthy programming practice.

## The Difficulty of Async Failures

But this all changes when some code is async! Think about it: we save the `ImagePost`
to the database, `AddPonkaToImage` is sent to the transport and the response is
successfully returned. Then, a few seconds later, our worker processes that message
and, due to a temporary network problem, the handler throws an exception!

That's... not a great situation. The user thinks everything was ok because they
didn't see an error. And now we have an `ImagePost` in the database... but Ponka
will *never* be added to it. Ponka is furious.

The point is: when you send a message to a transport, we *really* need to make
sure that the message *is* eventually processed. If it's not, it could lead to
some weird conditions in our system.

## Watching Failures

So let's start making our code fail to see what happens! Inside
`AddPonkaToImageHandler`, right before the real code runs, say if `rand(0, 10) < 7`,
then throw a `new \Exception()` with:

> I failed randomly!!!!

[[[ code('a0b5db52f2') ]]]

Let's see what happens! First, go restart the worker:

```terminal-silent
php bin/console messenger:consume -vv
```

Then I'll clear the screen and... let's upload! How about five photos? Go back
over to see what's happening! Whoa! A *lot* is happening. Let's pull this apart.

The first message was received and handled. The second message was received and
*also* handled successfully. The third message was received but an exception
occurred while handling it: "I failed randomly!". Then it says: "Retrying - retry #1"
followed by "Sending message". Yea, because it failed, Messenger *automatically*
"retries" it... which *literally* means that it sends that message *back* to the
queue to be processed later! One of these "Received message" logs down here is
*actually* that message being received for a *second* time, thanks to the retry.
The cool thing is... eventually... all the messages *were* handled successfully!
That's why retries rock. We can see this when we refresh: *everyone* has a Ponka
photo... even though some of these failed at first.

## Hitting the 3 Retry Max

But... let's try this again... because that example didn't show the *most* interesting
case. I'll select *all* the photos this time... oh, but first, let's clear the
screen on our worker terminal. Ok, upload, then... move over.

Here we go: this time... thanks to randomness, we're seeing a *lot* more
failures. We see that a couple of messages failed and were sent for retry #1.
Then, some of those messages failed *again* and were sent for retry #2!
And... yea! They failed yet *again* and were sent for retry #3. Finally...
oh yes, perfect: after being attempted once and retried again 3 *more* times,
one of the messages *still* failed. This time, instead of sending for retry #4,
it says:

> Rejecting AddPonkaToImage (removing from transport)

Here's what's going on: by default, Messenger will retry a message three times.
If it *still* fails, it's finally removed from the transport and the message is
lost permanently. Well... that's not *totally* true... and there's a bit more
going on here than it seems at first.

Next, if you look closely... these retries are *delayed* at an increasing level.
Let's learn why and how to take *complete* control over how your messages are
retried.
