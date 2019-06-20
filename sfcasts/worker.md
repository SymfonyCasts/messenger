# Worker

Coming soon...

The refresh the page now that our messages aren't being handled immediately. The most
recent four photos don't have Ponka attitude yet, but they were sent to our doctrine
transport. So if you can see that there's a messenger messages table here which has
four rows in it, which holds those messages. So to actually process those messages,
we need something that can read those objects out one by one de serialize them back
into the original object and then pass them to the system like normal so that Punko
can actually be at. The cool thing is that we're going to do this from the command
line so that the user never has to wait for this to happen. The way you do this is by
running bin Console, messenger colon consume. This is going to read the messages from
our transport one by one and process them. And right now you're not gonna see any
output from this, but it is working behind the scenes. Check this out, I refresh.
There it is. It already did. All four messages.

Yeah.

To make this a bit more interesting. As you can see here, it says running with Dash v
V because this will actually show some log messages. Now you notice the command is
runs forever. It just sits there continually waiting for messages. And you'll see the
same thing if you're on with dash being V, it's not as waiting, it's just waiting for
messages to uh, get added to the queue. Right now if we look inside of our Q over
here, there are no messages because all those messages are processed and they were
removed from the queue. So let's go over here now and let's upload. I'll say five
photos has and you'll see our fast. These are going to upload. Whoa, that was
awesome. And then over here as you can see them actually being processed, you can see
it says received the message, the message was handled, it was successfully handled.
And then it goes onto the next message and the next message and the next message and
it's actually done. So for refresh over here, you can see punk was added to all of
those. So let's try this again. What maybe like five different messages. It handles
them. And if we refresh here, yeah, can I see them handled little by little? There's
Ponka Yup. All the way up

there it is and there it is.

Okay.

So that is a really cool thing. I mean it'd be even cooler if my friend had
automatically updated one punk at it, but that's something totally different. But we
get this really nice user experience on the front end where it happens really
quickly. And then at the backend, um, it's handling all of those messages. So this
message consumed command is something that you'll have running on production all the
time. And we're going to talk about how you do in production a little bit later. The
cool thing is you can run this command on your web server or you could actually
deploy this to a totally different server which has your application. And the cool
thing about that is then that server can work on these messages without making your
web server, uh, use CPU for anything other than serving your site back to the user.
But there is one kind of weird problem right now. If you look at this, I'll refresh
the page. The original ones all said Punko visited 13 minutes ago, 11 minutes ago,
but now these all say punk is napping. Check back soon. And the reason that's the way
that message works is on our image posts entity.

Okay,

there is a pumpkin added at date time field, which records when punk was added. So
there's original ones down here. We're back when it was synchronous and it was being
added successfully, but now pumpkin is being added, but it looks like that date isn't
being updated. And in fact if we go over here,

I'll say select star from image post and my /g trick to make it print nicely. You can
see all the way back in the beginning, punk added that was being set, but now it's
all no. So our images being processed correctly, but for some reason this isn't being
updated even though if we look in our handler, yeah, right here, image posts, aero
mark, Ponca added. If I held that, that actually sets that property. So let's figure
out what's going on next and fix this and learn a little bit more about the way that
we should structure our messages.