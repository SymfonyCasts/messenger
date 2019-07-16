# Partial Handler Failures & Advanced Routing

We just broke our image deleting process into smaller pieces by creating a *new*
command class, a new handler and *dispatching* that new command from *within*
the handler! This... technically isn't anything special, but it *is* cool to see
how you can break each task down into as small pieces as you need.

But let's... make sure this actually works. Everything *should* still process
synchronously. Delete the first image and... refresh to be sure. It's gone!

## Thinking about Failures and if Messages are Dispatched

Before we handle the new command class asynchronously, we need to think about
something. If, for some reason, there's a problem removing this `ImagePost` from
the database, Doctrine will throw an exception right here and the file will never
be deleted. That's perfect: the row in the database and file on the filesystem
will *both* remain.

But if deleting the row from the database is successful... but there's a problem
deleting the file from the filesystem - like a temporary connection problem talking
to S3 if our file were stored there... that file would... actually.. *never*
be deleted! And... maybe you don't care. But if you *do*, you could wrap this
*entire* block in a Doctrine transaction to make sure it's *all* successful before
*finally* removing the row. Of course... once we change this message to be handled
asynchronously, deleting the actual file will be done *later*... and we will be,
kinda "trusting" that it will be handled successfully. We're going to talk about
failures and retries *really* soon.

## Routing the Message Async

Anyways, now that we've broken this into two pieces, head over to
`config/packages/messenger.yaml`. Copy the existing line, paste and route
the new `DeletePhotoFile` to `async`.

[[[ code('06eb9c3cf8') ]]]

Cool! With any luck, the row in the database will be deleted immediately... then
the *file* a few seconds later.

And because we just made a change to some handler code, go over, stop our worker
and restart it:

```terminal-silent
php bin/console messenger:consume -vv
```

Testing time! Refresh to be safe... and let's try deleting. Check out how much
faster that is! If you scoot over to the worker terminal... yea, it's doing all
*kinds* of good stuff here. Oh, and fun! An exception occurred while handling one
of the messages - a file wasn't found. I think that's from the duplicate row
caused by the Doctrine bug a few minutes ago: the file was already gone when the
second image was deleted. The cool thing is that it's already retrying that message
in case it was a temporary failure. Eventually, it gives up and "rejects" the message.

Let's try this *whole* crazy system together! Upload a bunch of photos... then...
quick! Delete a couple! If you look at the worker... it's all beautifully mixed
up: a few `AddPonkaToImage` objects are handled here... then `DeletePhotoFile`.

## Routing with Interfaces & Base Classes

Oh, and by the way: if you look at the `routing` section in `messenger.yaml`,
you'll *usually* route thing by their exact class name: `App\Message\AddPonkaToImage`
goes to `async`. But you can *also* route via interfaces or base classes. For
example, if you have a *bunch* of classes that should go to the `async` transport,
you *could* create your very own interface - maybe `AsyncMessageInterface` - make
your messages implement that, then *only* need to route that interface to `async`
here. But be careful because, if a class matches *multiple* routing lines, it
*will* be sent to *all* those transports. Oh, and last thing - in case you have
a use-case, each routing entry can send to *multiple* transports.

Next: remember how the serialized message in the database was wrapped in something
called an `Envelope`? Let's learn what that is and how its *stamp* system gives
us some cool superpowers.
