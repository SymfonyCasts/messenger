# Async Transport

Coming soon...

So far separating the instructions of what we want to have happen. We want to add
Ponca to this image post from the logic that actually does that is just a nice
coating pattern, but it unlocks some serious possibilities. If you think about it.
Now that we have all of the instructions on what we want to have happen, couldn't we
in theory, instead of processing this object, instead of handling this object
immediately, couldn't we save this somewhere else for later and then later have
something read this and then process the message. That's basically how a queuing
system works and the advantage of doing something like this is that we can put less
load on our web server and we can give the user a faster experience. Like right now
when they click to upload a file, it takes a few seconds before it actually pops over
here. Not the biggest deal, but not the most awesome thing either.

In Messenger. The way that you do that is via a system called transports, opened up
config packages and messenger dot Yammer. You can see some transports he under here.
Basically what we're gonna do is we're going to say, hey, when I create an ad punk to
image object, instead of handling it immediately, I want you to send it somewhere
else to transport transport's basically a Q. It's something that's going to hold onto
that object for later. And then in a little bit we're going to run it another
process. We're gonna run a console command that actually started to reading those,
uh, objects out and handling them asynchronously.

So Messenger supports a variety of different transports. And if you look in your dot
[inaudible] file, you can see the three that are supported right now described a MQP,
which is rabbit Mq doctrine or Reddis. It could be as the most powerful, but they,
but any three of those is probably going to work just fine. Since I'm already using
doctrine in this project, I'm going to use the doctrine transport. So let's uncomment
that out. Now notice this colon colon default here that basically says, I want to use
the default connection of doctrines. So it's going to reuse your doctor in connection
to store a new table inside of your database. Next and Messenger.yaml. I'm going to
uncomment this async transport. The name of this key async isn't important. It can be
anything and you'll see how that's going to be used in a second. Now if we

okay

only made those two changes and went back over here and uploaded a new file, it's
going to make absolutely change are object is still being handled immediately and you
can see punk as still being added to the image immediately. Once you have transport
set up, what you're gonna do is start routing different message objects to that
transport. So right now you can see our routing candor here is empty of events, some
comments, which means that when we dispatch our add pumpkin to image message, it
doesn't match any routing and so it's just handled immediately. It's handled
synchronously. Now check this out. Let's say APP message, add Ponca to image and
we're going to map that to async. As soon as you do that, it makes a big difference.
Watch this, watch closely. How fast did this returns open and boom, it pops over
here. That was faster than before. And you can see punk is not there. It didn't
actually handle our message. And actually if we try it again cause the cache will be
warmed up, I'll be even faster. Boom. See how fast that popped over here. So now
instead of actually handling this message immediately, it's being sent to our
doctrine transport. What does that actually mean? Well check this out.

Yeah,

I'm actually going to use my SQL here to connect directly to my messenger tutorial.
Um, database inside here. I'll say show tables and check this out. We expected
migrations, versions and image posts, but we now have a brand new table called
messenger messages. We select from that.

Okay,

check this out. There's two messaged to rose inside of here

and the body of it, this is actually our object serialized. If you actually look
inside of here, there's a whole bunch of other details you're going to see
information about that uh, uh, thing. You can actually see our ad punk into image is
actually serialized inside of this and ask them dates about like one, it was created,
etc. So it's actually saves for later and yes, it's really cool. By default,
messenger automatically sets up this table when you need it, if you didn't want it to
do that. I'm going to open a new tab. There is a way to disable that setting. It's
called auto set up, and if you did do that,

okay.

And there's actually a command in here called set up transports. You can run that to
actually manually set up the transports, but that doesn't do right now because the
transports were already set up the lake. All right, so now that something else is
keeping track of our messages and we go over here and add two more, then we're going
to see over here that we now have four rows inside of here. Next one we need to do is
we need something to actually read these messages one by one and actually start
processing them. So we can actually put pong kind of image that's called a worker and
we're gonna talk about it next.