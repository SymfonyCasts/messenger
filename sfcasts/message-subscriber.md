# Message Subscriber

Coming soon...

Open up delete image post handler. One of the things that message the message bus
needs to know is the link between the delete image post message object and then
delete image posts handler. It needs to know and we know that that the way that
Messenger knows this is that all of our handlers need to implement this message
handler interface. And once we do that Symfony looks at the type n for the invoke and
that helps it know that they bleed. Image posts should be handled by this class and
we can see this if we go to our own terminal and run bin Console, debug messenger, it
shows up here that the bleed image post is handled by the lead image post handler
thanks to that interface and um, type in combination. We also in config services,
that Yam all got a little bit fancier. My kind of separating out my using service
auto registration, we were actually able to add a tag to all of our command handlers
that meant handles and query handlers.

And what that did is it actually, we added a little bus configuration there and that
basically told somebody, hey, I want you to make that connection between the delete
image post message and the delete image post message handler. But I only want you to
um, tell the command and boss about that. That's the only bus that that message is
going to be dispatched to. So when you're on debug a messenger, you can see that
communicated the command buses, aware of the delete image, post and delete image post
handler connection. The event bus has is aware of the events and the query or buses
are aware of the relationship between get total image count and get total image count
handler. So this is a review of stuff that we already know.

Now, of course in the system there are a couple of things that you can't change. For
example, you can't change the fact that this method is called underscore,_invoke.
That's just what Symfony looks for. And because a class can only have one_underscore
invoke a method, it means that you can't have a single handler that handles multiple
messages. And honestly that's not that big of a problem. I don't that I typically
only have one. I typically only want a handler to handle one type of message. But
this, the way that you sit, the way that you configure a message, uh, being tied to a
handler is actually more highly configurable. Let me show what I mean. Instead of
implementing a message handler interface, we can optionally implement message,
subscriber interface. And real quick if I open up that you can say this extends
message handler interface. So we're still effectively implementing the same interface
but now we're forced to have one new method called get handled messages. So I got to
the bottom of this method,

got a Code -> Generate menu or command and an amount slightly moment "Implement
Methods" and add that as soon as we implement this interface, instead of magically
looking for the underscore,_invoke method and looking at the type end, it's actually
going to call this method and we are going to tell it all of the messages that we
handle. So the easiest thing can be in here is you can say yield and we'll just say
bleed image, post ::class. Okay,

who did that and went back and ran debug messenger. You'd see that there's no change.
It's still says it's distill, knows that the we, it was supposed handler is tied to
delete image post.

Cool,

but technically this type head does not need it anymore. If I deleted that Taipan and
rerun, you can say it still knows that connection because of our get handled
messages. So that's not that interesting. But now that we have this, we can start
adding other options that serve. Describe this connection. For example, we can say
method_underscore invoke. So suddenly I'm going to keep calling this_and Skinner
invoke, but I could call it handle message now and call it handle message down there
and we're allowed to call that method something different. That's really important
because a, we could actually put another yield down here and have a second and have
his handler handle a second type of a message

[inaudible]

and have that call. Some other method

[inaudible]

and there were a couple other bits of configuration you can do here. Um, in a few of
them aren't really that important. One of them that you're going to see is called
priority, which you can set to, let's put which slots set to, for example 10. Now
earlier we talked about priority transports. So if you look in config packages,
messenger.yaml we have this, uh, async transport and this Async, uh, priority high
and we're routing some messages to async and other messages to async priority height.
Now the reason that the async priority high becomes the high priority transport is
simply that when we consume the messages, we tell our worker to read everything from
async priority high first, then read everything from basing this prior to here is
much less important.

[inaudible]

this just says if delete image post had two different handlers for it, then this
handler would be called first because that's priority zero. And the default priority
is 10 and the default priority is zero.

[inaudible]

but if you sent 10, 10 different, um, you've sent 10 messages to the same transport,
um, those messages are still going to be a consumed in the order that they were sent
there. The priority is going to have no effect on the order in which the messages are
consumed from the, the transport. So it doesn't end up being that important of a
thing. The last one, which is kind of interesting, uh, but a little more advanced is
you can say from transport. So if you look, this delete image post is being routed to

okay.

Is actually not being routed anywhere. This is a synchronous message. Well, let's
pretend that it's being routed to the ASYNC transport.

Okay?

Now the from transport is kind of confusing. That's why I don't love this as a
feature. But what it allows you to do is if you have a [inaudible] particular, if you
have a message that has multiple handlers and you want each handler to be handled by
a different transport. Now what you can do is for example, just to make this a little
more realistic, we can say

we'll set, we'll pretend that we do want our delete image posts to be handled. Once
that's done, this both async and async priority high. Now that on its own should be a
little bit weird. That's going to mean it's actually going to be sent to both
transports and when we consume those, our delete image was handled. It would normally
be run two times each time it would read or consume the message once from async and
run our handler and then consume it again from [inaudible] priority high and run the
handler. That's actually backwards. But by adding this from transport Async, it means
that when the delete image post is consuming from Async, it will run this handler,
but when it's consumed from async priority high, it won't run this handler. Why would
we ever do that? Because it allows us to have a second handler for this same delete
image post and that second handler can have from transport aceing priority high. So
you effectively send your message to two transports, but one transfer only runs one
handler and another transport only runs another handler. So you can have your two
handlers run asynchronously of each other. So that's a little bit more advanced. I'm
actually gonna comment that out cause it doesn't make any sense for this and go and
remove that routing there.

Okay.

And that's basically it for the options. Uh, if you look at the message subscriber
interface, it kind of talks about some of these. Um, and, and for the most part,
they're a little bit confusing, but if you need to do a little bit more advanced
stuff, the message subscriber interface is a, is how you can do that.