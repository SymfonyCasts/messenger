# Sending Handlers to Different Transports: from_transport

The last option I want to mention *is* interesting... but can also be confusing.
It's called `from_transport`.

If you look at `messenger.yaml`, this `DeleteImagePost` isn't being routed
anywhere, which means it's handled synchronously. Let's pretend that we want to
handle it *asynchronously*... and that we're routing it to the `async` transport.
Set `from_transport` to `async`... 

[[[ code('a5ba58b0a5') ]]]

then temporarily route this class to that transport in `messenger.yaml`.

Now, pretend that the `DeleteImagePost` message actually has *two* handlers...
something that's very possible for events. Assuming that we did *not* add
this `from_transport` config yet, if you sent `DeleteImagePost` to the
`async` transport, then when that message is read from that transport by a worker,
*both* handlers will be executed one after another.

But what if you wanted to, sort of, send *one* handler of that message to
*one* transport, maybe `async_priority_high`, and *another* handler to
*another* transport. Well, in Messenger, you don't send "handlers"... you send
messages... and when Messenger consumes a message, it calls *all* the handlers
for that message. Does that mean it's impossible to make one handler of a message
"high" priority and another one low? Nope! This workflow *is* possible.

## Route to Two Transports

First, route `DeleteImagePost` to *both* the `async` and `async_priority_high`
transports. 

[[[ code('3fb5eeef3c') ]]]

If we *only* did this, the message would be sent to *both* transports,
it would be consumed *two* times, and *every* handler would be called *twice*...
which is totally *not* what we want... unless each handler is baking cookies... or
something.

But when we add this `from_transport` option set to `async`, it means that this handler
should *only* be called when a `DeleteImagePost` object is consumed *from* the
`async` transport. If we configured a *second* handler with `from_transport`
set to `async_priority_high`, that handler would *only* be called when the message
is being consumed from *that* transport.

In other words, you're sending the message to *two* transports, but each transport
knows that it should only execute *one* handler. This allows your two handlers
to be queued and executed by workers independently of each other. It's a
*really* powerful feature... but because Messenger is centered around sending
*messages* to transports, over-using this *can* be confusing.

Let's comment that out and remove the routing config.

[[[ code('194442abf3') ]]]

That's basically it for the options you can pass here... though you can always
check `MessageSubscriberInterface`: it talks about what's available.

Next, let's up our queueing game by changing from the Doctrine transport to
RabbitMQ - also commonly referred to as AMQP. It's buckets of fun!
