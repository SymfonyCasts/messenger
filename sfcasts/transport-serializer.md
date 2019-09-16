# Transport Serializer

Coming soon...

If we send messages to our,

okay,

if messages somehow get sent to our new messages from external cue,

we're set

from an external system, they're probably going to be sent as some JSON that looks
something like this. We've set up a transport now called external messages, but when
we consume those messages, it explodes because by default a messenger uses a PHP
serializer to actually take that data and turn it into objects.

So if you're consuming from an external system, you're going to need a custom
serializer on your transport, which actually is a really nice and easy thing to do.
So inside of our app Messenger directory though, this could go anywhere. Let's create
a new PHP class called external JSON Messenger. Serializer. The only rule is that
this needs to implement a serializer interface. Careful. There are two of them. One
of them is from the serializer component. We want the one from the Messenger
Component. I'll go to a code generate or command end. Go ahead "Implement Methods"
and add the two that we need decode in Incode. So this is very simple. When we send a
message through a transport that uses this serializer

yeah.

The transport will pass us the envelope object that contains the message. And our job
is to turn that into the, uh, into the final, um, uh, package that will be sent to
the, um, that will be sent to the w that will actually be send to the transport.
Notice this returns an array, but if you look at the serializer interface, um, that's
because we actually return an array with a body key and a header scheme. So it's up
to us inside of Incode to turn it into the body and headers that we want ultimately
sent to rabbit MQ or doctrine or wherever. Of course, we're not gonna use this
transport for sending. So instead in, in code, I'm gonna throw in an exception that
says transport and serializer not meant for sending messages just in case we do
something silly and try to use it.

Now when you consume from a transport, it's going to take the it's gonna call the
decode message in. Our job here is to take the data that was being received from our
transport and turn that back into an envelope object. Now once again, I felt like at
the serializer interface, and I'll tell you actually that this encoded envelope is
going to have a body key and a headers key. So the first thing I want to do inside of
here is just say body = encoded envelope, left square bracket body, and then headers
= encoded envelope, Lasker racket headers. Now the body is going to be this JSON Body
here. You notice there is a headers key. We're not using a app, so we'll talk about
some headers and a little bit, but right now our headers are going to be empty and
the headers are not going to be important [inaudible] so because this body will not
be the JSON, our end goal is transported to actually turn this JSON into a log Emoji
object and then stick that into an envelope.

So we can make this very, very simple. We could say data = JSON Decode of body and
true to turn it into an associate of array. And I'm not going to do any air checking
at this point. I'm just gonna assume everything works. Now we can say message = new
log Emoji data, lesker bracket Emoji because that's the key that we've decided we're
going to expect there to be on the JSON. Then finally we need to return an envelope
object and as a reminder, an envelope is just a small wrapper around some sort of
message with some optional stamps. So down here we'll say return new envelope

and inside of there we'll put our message and that's it. This is a fully functional
serializer used for a reading, things from acute to make our transfer use that we
already know that there's a Siri Lazur option that you can pass to each transport. So
let's add serializer and we'll put the ID of our service, which is actually going to
be the class name apps. Last messenger /and then I'll go copy the class name external
at JSON at Messenger serializer. This is actually why we created a separate transport
with a separate queue because we're going to have this one transport and messages
from this one queue. Go through our external JSON Messenger serializer but our other
two transports async pacing priority high. They're still gonna use the other s
simpler PSPC realizer which is going to be perfect. All right, so let's try this.
First thing I'm gonna do is go over to one of my open tabs and tail dash f Var, log
Dev, that log and I'll clear the screen.

And then over here and my other thing I'm going to consume from the external messages
transport. Perfect. So there's no messages yet. So it's just waiting. And what we're
hoping is that when we send this, uh, publish this message to our transport, it will
be consumed by our worker and that will cause a log message to be displayed. So let's
try it. Publish and let's move over. There it is. We got an important message.
Cheese, you can see, receive the message and it handled it down here you can see. So
that is the key to reading things from an external transport. There were a couple of
other details to top out and we'll talk about those next.