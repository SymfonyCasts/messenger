# Sent Received Logging

Coming soon...

Here's the goal, to leverage our middleware and the fact that we're adding this
unique id to every single message that's dispatched to log the entire life cycle of
each message into a new log file. Like I want to see like when this message was
originally dispatched, when it was sent to the transport, when it was received from
the transport, when it was handled, all that stuff. So first I'm gonna actually
configure a new law handler. So in messenger can fake packages Dev. So this will only
be in the Dev environment. I'm going to add a new

no, that's not true

dad, mom and don't animal the animal. Actually I'm going to create a new um, right in
config packages. New File here called monolog.yaml on foot monolog there. And I'm
gonna have to do is add a new channel here called Messenger_audit. I created a new
file here so that this file is used in all environments. And what that's gonna do is
it's actually going to create a new service and the container which logs to this
channel and she handles kind of a logging categories. So this creates a new lager in
the service in a new category you can see by running diva Container Messenger_audit.
Now I'll find this new monolog, a lager messenger audit service, and we can use this
new service to log messages to a specific spot. Now back up in the Dev monolog.yaml
I'm actually going to copy the main handler. Let's call this one messenger. That key
is not gonna be important here. And then we're gonna log to a file called the
messenger dot log. And here we're going to do is log only that messenger_audit a log.
So if we use that new lager service, then those log messages are gone. All of them
are going to be sent directly to this file.

Okay.

Now in order to make this Ottawa Arabelle, I'm going to go into my service.yaml and
under my global binds I'm going to add one new global bind, which I'm going to call
messenger audit logger. And on point that at that new service that I have. So I'll
copy that from my terminal and say at monologd out logger, that messenger audit. So
this means I can use this argument name in a service and it's going to pass me that
new lager service. Okay, so let's do that. I'm gonna close up those monolog files.
Let's do that. Inside of our audit middleware, I'm gonna add a public
function_underscore construct I on my lager interface and then I'm going to call
that,

whoops,

call that the Messenger audit logger

that I put in my config

and I'm just going to call the property itself just lager. So we'll say this->logger
= messenger audit longer. Now down here, this is, we're going to do the log in and
first I'm going to remove the dumping. I'm actually going to create a variable called
context. You're going to see where this is used in a second, but this is just to be
some extra information that we pass to each law call. And I'm going to create an I d
a a key called ID, which is we're going to set to the unique id and then another one
called class, which is actually made the class name of the message that was
originally sent, which is going to be get class envelope,->get message. So they get
messages are going to give us our underlying message that we originally sent. Now the
confusing thing about middleware is the middleware is actually called in two
different situations. It's called when you initially dispatched the message. So for
example, in image post controller, when we dispatch the message originally, that's
obviously going to call all of our middleware. The second time it's called though is
from the worker. So let me pass, when are you Ron?

In console, messenger colon consume each time it reads a message from the queue. The
way it actually handles that is that it re passes it into the dispatch method. So the
middleware are also called in that situation is the tricky thing is writing
intelligent code inside of your middleware to figure out what situation you're in and
the way to determine, hey, was the message just dispatched or was the message just
received from a transport is by reading a couple of special stamps. So check this
out. We can say if envelope aero last received a stamp ::class, then we know this was
just received from a transport. This was just processed by one of our workers. And
that's because when the worker, when the Messenger consume a process, reads a message
off the cube, it adds that received stamp as a way to say this was just received from
the transport. So we can say this Arrow, lager->Info.

And here I'm just going to use a special syntax where it's like curly brace id and
I'll say received and handling. And then here I'm just like curly brace, class, curly
brace, and then pass the context as a second argument. So context is just an array of
information that you can pass to the logger along with your message. Um, and they're
really cool thing about the context is you can put these little wild cards in the
message and they're going to get filled in. You'll see that in a second so that if
this was just not received, then we can say this->lager->Info. We'll start the same
way.

And here I'm going to say handling or sending class and past context because at this
point we're not really sure this could be a situation where it's an, it's a
synchronous message. We're going to handle it immediately or it could be a situation
where we are going to um, be sending this message to a transport. All right, so let's
try this first. I'm going to start my worker. I'll do dash V v a sink. Looks like
that was still use processing some older messages from earlier. So we'll let it
finish that and then I'll clear that config. The other thing to do is I'm going to
open up a new tab here and I'm just going to touch that new vlog file that's about to
be created, which if we look at our logging config is going to be called Messenger
dot log. Some of that touch, that file so that I can start tailing it. Tail dash f
Bar Log Messenger dot log up. You can actually see a couple of those messages in that
didn't even need to touch it. Those are from those previous matches that were being
handled. Cool, so we have a fresh thing that we can look at here. All right, let's
try this. Let me go over and let's upload just

one new photo

spin back here and yes, check it out. You get handling or sending and then you get
received in handling. So that worked perfectly. Just one more. What we could do even
better than this because it's a little weird to say handling or sending, we should
be, we should really know like that we're handling this or we're sending it or
receiving it. It's those three different situations and this is a key thing with
middleware. It's a little bit strange here, but what the, the stack of next stuff but
that's going to do is it's all it's going to start calling the next middleware and
eventually one of the next middle. Where are the core middleware that either handle
or send it. So what I really want to know here is I really want to know what's this.
Is this message going to be handled or was this message just sent?

The problem is by the time this code runs here, the sending hasn't happened yet, so
there's no way we can look on the envelope and say were you just delivered to a
transport or not? So this is not totally clear. Watch what I'm about to do here. I'm
actually gonna send set. This was the envelope = stack Arrow, next->handle and I'm
going to move that above our code. Then it down on the bottom, I'm going to say
return envelope. Now if we just made that change, nothing really happened except that
we've now made it so that the handling or sending of the is going to happen first
because we're going to execute the core middleware first and then we log our message
after that. But practically speaking, it doesn't make much difference. However,
notice that when we get back a new envelope from stack Arrow, next->handle, and when
a message is sent, a stamp is put on that message to market as scent. What this means
is we can add in else if down here and say if envelope aero, last cent stamp ::class,
then we know that this message was in fact not handled. It was sent. So Watch, I'll
say this->logger,->Info and the same id and I'm going to say sent class. And then
down here it's not, it's not handling your Sunday.

Whoa, Whoa, Whoa, whoa. Uh,

down here it's not handling or sending, we know this is actually handling and
actually handling it synchronously and appear. This is still, this is still correct,
received and handling, but I'm just gonna change this to receive. So your mess, kind
of like received scent and handling sink. All right, so let's clear our log here. I'm
going to restart our workers. I'll clear that and let's try and message here.

So go over

and let's go to our logs. And yes, you can see scent and then it was received and
it's even more interesting. I'll hit enter a couple of times. If we delete a message,
you can see, look at the, it's really obvious now with the unique id here. You can
see that the, this first unique id was sent, then there's a second unique and d that
was handled at synchronously. And then the original message that was sent was
received in handle. So if you need some, uh, some way of really tracking your
messages in your system, this is the way to do it. And now you understand a lot more
about the internals of how messenger actually works.

Okay?