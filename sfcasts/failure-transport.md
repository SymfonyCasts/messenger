# The Failure Transport

We now know that each message will be retried 3 times - which is configurable - and
then, if handling it *still* fails, it will be "rejected"... which is a "queue"
word for: it will be removed from the transport and lost forever.

That's... a bummer! Our last retry happened 14 seconds after our first... but if
the handler is failing because a third-party server is temporarily down... then
if that server is down for even just *30* seconds... the message will be lost
forever! It would be better if we could retry it once the server was back up!

The answer to this is... the failure transport!

## Hello Failure Transport

First, I'm going to uncomment a *second* transport. In general, you can have as
*many* transports as you want. This one starts with `doctrine://default`. If
you look at our `.env` file... hey! That's *exactly* what our
`MESSENGER_TRANSPORT_DSN` environment variable is set to! Yep, *both* our `async`
and new `failed` transports are using the `doctrine` transport and the `default`
doctrine connection. But the second one *also* has this little `?queue_name=failed`
option. OooooOOOOooo.

[[[ code('81c981f68d') ]]]

Go back to whatever you're using to inspect the database and check out the queue
table:

```terminal
DESCRIBE messenger_messages;
```

Ah. One of the columns in this table is called `queue_name`. This column allows
us to create *multiple* transports that all store messages in the same table.
Messenger knows *which* messages belong to which transport thanks to this value.
All the messages sent to the `failed` transport will have a `failed` value... that
could be anything - and messages sent to the `async` transport will use the default
value... which is `default`.

## Configuring Transports

By the way, each transport has a *number* of different connection options and there
are two ways to pass them: either as query parameters like this *or* via an expanded
format where you put the `dsn` on its own line and then add an `options` key with
whatever you need below that.

What options can you put here? Each transport *type* - like `doctrine` or `amqp` -
has its *own* set of options. Right now, they're not well-documented, but they
*are* easy to find... if you know where to look. By convention, every transport
type has a class called `Connection`. I'll press Shift+Shift in PhpStorm,
search for `Connection.php`... and look for files. There they are! A `Connection`
class for Amqp, Doctrine and Redis.

Open the one for Doctrine. All of these classes have documentation near the top
that describe their options, in this case: `queue_name`, `table_name` and a few
others, including `auto_setup`. Earlier, we saw that Doctrine will create the
`messenger_messages` table automatically if it doesn't exist. If you don't want
that to happen, you would set `auto_setup` to `false`.

The transport with the *most* options can be seen in the Amqp Connection class. We'll
talk about Amqp later in the tutorial.

## The failure_transport

Anyways, back to it! We now have a new transport called `failed`... which, despite
its name, is the same as any other transport. If we wanted to, we could *route*
message classes there and consume them, *just* like we're doing for `async`.

But... the *purpose* of this transport is different. Near the top, there's another
key called `failure_transport`. Uncomment that and notice that this *points*
to our new `failed` transport.

[[[ code('5bc9ca3d83') ]]]

What does it do? Let's see it in action! First, go restart our worker:

```terminal-silent
php bin/console messenger:consume -vv
```

Woh! This time, it *asks* us which "receiver" - which basically means which
"transport" - we want to consume. A worker can read from one *or* many transports -
something we'll talk about later with "prioritized" transports. Let's consume just
the `async` transport - we'll handle messages from the `failed` transport in
a different way. And actually, to make life easier, we can pass `async` as an
argument so that it won't ask us which transport to use each time:

```terminal-silent
php bin/console messenger:consume -vv async
```

Now... let's upload some images! Then... over here... pretty quickly, all 4 of
those exhaust their retries and are eventually rejected from the transport. Until
now, that meant that they were gone forever. But this time... that did *not*
happen. Before removing the message from the queue, it says:

> Rejected message `AddPonkaToImage` will be sent to the failure transport "failed"

And then... "Sending message". So, it *was* removed from the `async` transport,
but it still exists because it was sent to the "failed" transport.

How can we see what messages have failed and try them again if we think those failure
were temporary? With a couple of shiny, new console commands. Let's talk about those
next.
