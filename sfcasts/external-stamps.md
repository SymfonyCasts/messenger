# External Stamps

Coming soon...

Forget about asynchronous messages and external transports and all that stuff. Just
for a minute. And Open up `ImagePostController`. As a reminder, when you dispatch a
message, you actually dispatch an `Envelope` object, which has a little message inside
of it and some optional, um, stamps which add extra information, uh, about your
message. If you just dispatch a message directly into the message bus. Um, the
message bus just creates an envelope for you. So there's always an envelope being
sent into the messenger or dispatch. Now when you call `$messageBus->dispatch()`, it
actually returns a new envelope.

I want you to `dump()` that whole `$messageBus->dispatch()` line, then move over. Let's
upload a new photo. And then after the uploads, I'm gonna go down here and open my
profiler for that request and go down to debug. Perfect. So you can see the end when
it actually returns is an `Envelope` and it has the same message inside of it. The 
`AddPonkaToImage` that we originally passed to it. But this message actually has additional
envelopes. So as a reminder, when you dispatch something into the message bus, it
goes through a bunch of middleware and those middleware can add extra stamps to the
message. So if you opened up stamps here, there are now 5 stamps. The first to
`DelayStamp` and `AmqpStamp`, no mystery why those are there. Those are there
because we originally added them when we dispatched the envelope. The last one, the
`SentStamp`, this is something that's added when it goes through the send message
middleware. So the message was dispatching to the message bus.

Because we've configured this to be routed to a `async_priority_high`. Then the
send message middleware does that and it adds a little `SentStamp` here, which just
basically as a marker that says this message was sent, it's not a particularly
important. Other than that

well check out this `BusNameStamp`. If you kind of opened that up, you'll see that
the `BusNameStamp` literally contains the, uh, name of the bus that this message was
sent through. So as a reminder, if you look in, um, `messenger.yaml` at the top,
we actually have three buses. `command.bus` `event.bus` `query.bus`.

so why do we add a bus name? Why is a bus named stamp added to this? It seems sort of
silly. We're sending the message to the command bus. So why do we have a stamp?
That's why did we add a stamp that, that a records this.

Well the answer is that when your worker consumes a message like we've just done

over here, what happens internally is that once our c realizes returns this `Envelope`
with our `LogEmoji` inside, what's messenger does, it takes this object and actually
dispatches that through the message bus. It basically does the exact same code here.
Something calls `$messageBus->dispatch($envelope)`, and it passes us. It passes it
the envelope that we returned from our serializer boys. I get

if we have multiple message buses, how does the work or command know which message
should go to which bus? Because in theory we could, the answer is

that is actually the point of this `BusNameStamp`. Messenger adds this stamp so that
when you, when your worker receives this message later, it can look at the `BusNameStamp`
and say, Oh, I need to dispatch this object through the command, that bus.
Right now if you look in our serializer, we're not adding any stamps to our envelope.
So what happens by default, what happens is that, uh, is that the worker just sends
it to the `default_bus`, which is the `command.bus` service, which for this one is
actually corrected the week. This is a command. We do want it to go to the command
bus, but I want you to know what's going on behind the scenes. By the way, normally
when we dispatch a message, a `UniqueIdStamp` has added. This is actually something
that we added. Let's show we have a, uh, an `AuditMiddleware` that's called and one of
its jobs is to make sure that the, a `UniqueIdStamp` is added to all messages.

So you might think if you want to have this nice unique id functionality, um, that we
might need to maybe add the unique eddy stamp manually instead of her serializer
right. But actually we don't, if you look at our log messages here, you can already
see that, see this little five d seven BC thing here, that's actually coming
from our `AuditMiddleware`. So when we received the message from, um, the external
queue, because it's sent back to the bus, it's sent back through the `AuditMiddleware`
the `AuditMiddleware` adds that `UniqueIdStamp` and then basically logs
it. I'll have to clean that up.

so this is a long way of saying, I don't want you to know as, I just want you to
realize that there are a couple of stamps that your system might be setting when you
dispatch a message. Normally when you consume a message from an external transport,
you're not adding those. And for the most part, that's probably fine. The `DelayStamp`
and `AmqpStamp` are irrelevant because that actually tells the, um, transport, how
to behave. But the `BusNameStamp` is one that you might want to do. So I'm actually
going to go into my `ExternalJsonMessengerSerializer` and I'm going to say 
`$envelope = new Envelope()`. And then at the bottom I'll return `$envelope`. And then the middle
I'm gonna say, uh, `$envelope = $envelope->with()` this is how you add a new stamp, 
`new BusNameStamp()`. And because we want this, this is a command. So we want it 
to be processed through the command bus. Um, I'll copy the `command.bus` service, 
uh, bus name and paste it there. And I'll pull all comments says, because this 
is only needed if you, if you need this message to be sent through a, 
the non-default bus.

So this isn't going to change anything in our application. If we go over here and,
uh, into a Rabbit MQ and published another message, actually even change that to
Emoji for and zoom back over. Yep. You can see everything is still working just fine.