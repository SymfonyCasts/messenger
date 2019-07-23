# Test In Memory

Coming soon...

Nose and then be able to like fix that. So you remember a second ago that in the Dev
environment only, we have a commented out now, but we basically overload our
transports to hand have them all handled synchronously. This is something you could
actually do in the test environment if you want it to. So that when you actually ran
these tests, it actually did it process those things, um, uh, synchronously. But if
we kind of think about it, all we really care about from this functional test
standpoint is that we get back there, you know, the successful response maybe that
um, our image was added to the database and really that the message was sent to the
transport cause because if you're worried about the, whether or not your handler was,
is able to run successfully, it's really probably about it to actually run a write a
unit or integration tests on the handle itself. So I really care here is, was the
message actually sent to the transport and right now there's not really a way for us
to do that. Sure. If we like killed our worker back here, maybe we could query the
database, but that's kind of a hacky way to do it and it only works with doctrine, so
here's what we're going to do in config packages Dev copy that Messenger Dot Yam we
created for the Dev environment and actually paste that into the test directory so
that this is only loaded in the test environment and uncomment out those transport
config and then replaced sync with in it dash memory.

We'll do it on both of them. The in memory transport's a really cool transport. In
fact, I'm going to open it up here. I'm gonna Hitch. I'm going to do shift shift and
say in the search for in memory transport and then you got to files the in memory
transport is basically fake transport. When a message is delivered to it, it just
puts it into a little scent variable so it just kind of keeps track of all the
messages that were delivered during a specific request and then you can use it to
actually, it doesn't actually process them, it doesn't send them to a queue, it
doesn't handle them. It just kind of stores them in memory temporarily. It's really
useful for testing. So the first thing to notice is that as soon as we do this, if I
go over to my work here, here, I'm gonna clear a second ago, every time we ran our
tests, our workers actually process things. So I'm going to come, I'm going to uh,
clear the screen there. Let me run the test. It still works, but there's nothing
being processed because it's not actually mean set the transport anymore, it's just
kind of stored on that object. And then when the test is over, it's just destroyed
and lost.

Okay.

The cool thing is though, we can actually inside of our test, go fetch that transport
object and ask it how many messages were delivered to you.

So behind the scenes, every transport actually has a service. So if you go over to
your terminal and run bin Console, debug colon container Async, you're going to get a
list of the actual service ids for our to transport some messenger transport, async
priority height. This is the second one here is actually, I'm going to copy that
name. That's actually the service id we want because we want to verify that the ad
pocket, the image object is dispatched and we know that that is sent to the async
priority transport. So back instead of our test, this is really cool. You can fetch
that transport off by saying transport = self colon, colon container->get and then
pays to messenger dot transport. That aceing priority hide this self colon. Colon
container is actually the service container. Have a special version of the service
container that was used on this request and you can use it to fetch any services out
that you want. Now if we dd the transport object here and then go over and run our
test again. Yup, we can see that this is a version. This is an in memory transport
object and by the way, you can even see that this little center property here, check
it out. It has one item in it so we can already see that this is actually working. We
just need to put this into an assertion.

Okay.

So to help my editor out here, I'm going to say that this is an in memory transport.

Okay.

And then below I'll say this assert count and then I'll say one exact one. The
message to, to be re returned. When I say transport->get, don't just want to do the
era here. You can say, um, there's a number of different methods on these. Um, the
one we actually want in this case is get, which is going to return all of the
messages that had been sent to the transport. An array of all of those messages.

Okay?

So when we run out, it passes because they're not actually calling the handler. We're
not actually sending the transport. We've actually simplified our test environment by
saying, by not doing any of those, um, but we now we can actually read those messages
off. And if you wanted to, you could actually fetch the specific message off and make
sure it had the correct information on it.