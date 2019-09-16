# Sending Json

Coming soon...

Now that we're sending our messages to rabbit MQ and consuming them from rabbit MQ. A
separate work flow starts to become a little more obvious in this may or may not be
relevant to your project, but one of the things that is really common with rabbit MQ
and q's in general is the idea that the code that sends a message might not be the
same code that Reese that consumes the message. So in our application, our Symfony
app is both responsible for sending the messages to our transports and then over here
we are actually also consuming those transports. So this same Symfony app is sending
the messages to rabbit MQ and then consuming them. But depending on what you're
doing, you might instead only be doing one of that. Like for example,

okay,

perhaps for some or all of your messages you want to send them to rabbit MQ, but
you're going to have some other application maybe written in some other language
that's actually going to consume them on high level. Like doing that. It's really
easy. If we, for example, only wanted to send things to this async transport but
didn't plan to consume them all we needed, we would need to change anything in our
code. It would just mean that we would send things to this async transport and then
we just wouldn't consume for it. We just consume from the other transports, but we
just wouldn't read the messages because something else is reading them. But when you
start thinking about sending messages to um, so that another system can use them, you
need to start thinking about the format of those messages, like what the actual
message body looks like. And this is not something we've talked too much about yet.

[inaudible].

So I'm gonna go into the messages. Normal Q here and just to make sure I'm a actually
purge this too just to make sure it's empty and let's delete something. So the uh,
are deleting images. I'll go to the ASYNC transport, so that should eventually route
to this queue. So let's go over the way to photo and then back here. We should in a
moment. Yup. There we go. See that message. Get back in here. I'm going to go down
here and actually get the message. This is the way you can actually see the message
in here. Now, for some reason on my application right now, this is not working. I'm
not sure what the problem is. So I'm going to right click and go to inspect element
and go to open my network tools and then get messages there. And right here you see
this little get end point. I can click this and it'll actually show me what that
message looks like. That's the important thing here is actually that payload, that
big, giant, ugly thing. That is what the message physically looks like in the queue.
That's the body of the message. If you don't recognize that that's a s a e PHP
serialized object.

This makes it, and when our, when our own messenger consumes that, it used the PHP
uncivilized function to turn that back into an object that's super great when you are
sending and receiving from the same application. But if you were sending this to a
different PHP application where all of these classes didn't exist, uh, including our
image posted deleted event class, um, it's not going to uncivilized correctly. In
fact, more broadly like this is just not, this format only really works if the same
application is I'm sending and consuming the message. As soon as a different
application is consuming your message, you're going to want to use a different data
format. Where you're probably gonna want to use is JSON or XML instead.

Okay.

Fortunately [inaudible]

[inaudible]

we can do that really easily. I'm going to purge that message out of the queue one
more time. That one, one more time. Then move over and in config packages,
messenger.yaml one of the keys that you're allowed to have below each transport is
called serializer. You can always also, there's also a global option. I'm going to
set this to a special string called messenger that transport, that
Symfony_serializer. There are two serializers that come built in for Messenger. The
first one is the PHP serializer. That's the default one. That's the one you just
stop. The Symfony. Serializer uses the Symfony serializer component, which means
you'll actually need to a require serializer if you don't already have that. The
Symfony serializer component is great because that's what that can be used to. Trans
transform all this into a JSON or XML. It uses JSON by default.

So now let's go over and let's delete one other photo over here and once again I'm
going to get that message and open it up and check it out. This is fascinating. Look
at the payload now it's file name and then the name of that file name, which makes
sense. If you look at the image, the class that's actually being sent as image posted
deleted event. So I'll go to our message, event, image posts, deleted event,
Symfonys, serializer object. Um, if you don't know too much about it, it actually
uses your gidor methods to get fields. We're not going to go into this too much, but
basically because we have a Gif file name here, it turned that into just a single
file name field. There's also has a spot down here called a headers. And this is
really interesting because it actually contains a bunch of information. It actually,
the class name that's a, this JSON should be turned back into as well as information
about the individual stamps. So it actually j sonified all the individual stamps. So
if we moved back over right now and consumed from that async transport, you'll see
that it does actually work. That's uh, this Symfony serializer.

It's smart enough to, uh, when it reads that message, it's smart enough to know that
it's going to take this JSON and it's going to DC realize it into this particular
class, but he's in the type header and then it's going to loop over all of these
different, um, stamp headers here and DCA realize each of those into that object as
well. So this means that, but the really nice thing is, is not, and the really nice
thing about the Symfony serializer is that because the payload is this simple file
name, image thing, you could send that to another application, maybe written in Ruby
or Java and it's going to be able to DC realize this stuff. So yes, it does contain
details about the specific PHP class names in case we want to consume this from our
own application. But you can also send this to any other application and they're
going to understand this, JSON. Now, why don't we just use this serializer by default
in messenger? Well, the reason is that the Symfony serializer a requires your classes
to be written in a certain way. Uh, that doesn't always make it convenience to be
used. So, um,

so if you are sending in consuming from your application, use the PSB serializer
cause it just makes your life a lot easier. But if you're sending it to another one,
you can do this.

Yeah.

Now additionally, if you are using the serializer, you can run bin Console, config
colon dump framework, messenger and discover a couple of other options under the
Symfony, uh, framework, messengers, serializer Symfony serializer thing. Here's where
you can from a format that you want JSON Our XML. And then there's a key here or
called context which use the pass options to the serializer. Um,

okay.

So that's it. If you're sending to another system, you'll probably don't want to use
the Symfony serializer so that you can send things via JSON or you can create your
own custom serializer, um, and do whatever you want.

Yeah.

But for now, let's remove this. Your are key. Next we're going to look at the other
side of the equation. What if something external actually sends messages to the
queue, not our Symfony app. And we want our Symfony app to consume them.