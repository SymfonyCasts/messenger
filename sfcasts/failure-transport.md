# Failure Transport

Coming soon...

Yeah,

we now know that messages or retry three times, which is something that you can
configure. And then if it's still has an air after three tries, then it is rejected,
which means it's removed from the transport. It's not really cute and the message is
lost forever. That is still a bummer because again, if you have a message and it's
temporarily failing because maybe some third party server is down, even though we
delayed ultimately nine to 2114 seconds between our first and our last tribe, that's
not a lot of time. If they have five minutes of downtime, then our message is lost
forever and that is a bummer. So to solve that, we have a really cool feature called
the failure transport. So check this out. First, I'm going to uncomment a second
transport. Just on a high level. You can have as many transports as you want.

Notice this one uses doctrine, colon, colon defaults. So if you look@your.in file,
that's actually Identos whole to our messenger transport. So basically with this set
up, both our ASYNC and our field transports are both using our default connection.
And doctrine. However, this one passes a little question mark Q_name = failed option.
I'm actually going to go back to my database here and I'm going to say describe a
messenger messages. That's the name of our messages table. And you can see in here
one of the things that hasn't, it isn't actually Q_name, which by default is just
messages. So this is a little column and what are the allows you to do is it allows
you to have multiple transports that are all storing messages into the same table and
then a messenger can tell them apart, um, based on that queue name value. So these
are gonna start at the same table, but they're going to be really treated like two
independent queuing systems.

By the way, there are a number of different options that you can pass to all of your
transports and there are two ways to pass them. You can either add them as query
parameters like this, which is sometimes convenient or you can use kind of the
expanded format of your transport where you put the DSN on its own line. And then you
have an options key where you can actually start putting options and below there. So
I want you to know that both are aware, both are available. The second thing I want
you to know is that the exact options that you have here aren't necessarily, aren't
all documented right now. They might be in the future, but by convention, every
transport type has a class called connection. So if I hit shift shift and PhpStorm
and search for connection dot PHP, then I'm gonna Click on files here.

You'll see that there's actually a true connection class for aim, QP doctrine and
Retta. So for example, if we look at the one from doctrine, all of these classes have
some documentation above him that talk about their options. So you can see Q name
here, table name, connection, um, and, and here's even that auto set up. And we saw
earlier, if you want to tell doctrine, hey, don't create the table on your own. I'm
going to create it manually. Um, another one here that's really important, uh, we'll
talk about this later, is aim. Qp is connection class has a lot of options because an
QP is a little bit more uh, powerful and complex. So you can see all the options
right on that connection class. Anyways, back to it, we have now activated a new
transfer called failed. And the real key thing is there's another key if you're
called failure transport, I'm an uncommon that and notice that this points at that
failed transport. This does something really cool.

We'll go over and restart or worker. And when we do that notice now it actually says
select which select receivers to consume. So now it actually says that we have to
transports. It's actually which transport do you want to read messages from? Uh, you
can read from the failed transport directly, but you're probably not gonna want to do
that. I'll talk about how you're supposed to use that transport in a second. So let's
just use our async and in fact actually to make life easier, we can actually just
pass that as an option to the command. So won't ask us every time. And then over
here, let's upload

for images.

Those was all uploaded really quickly. Then over here. Yep. You can see pretty soon
here, the criticals coming in should have four of them. Yup. There's four. They all
got, they all reach tried three times and they eventually were rejected from the
transport. And so normally a second ago those were gone forever. If we had checked
are a table in the database, they would have been gone. But now they are not gone.
And you can see it down here, you can see that it was really messy. You can see
rejected message add Ponka image will be sent to the failure transport. It was then
sent to the failure transport. That's what this line is saying here. It's a little
harder read. And then it was removed from the original transport. So it's been
removed from aim CUPE but it was sent it to our failure transport.

And the third transport's a really cool thing cause you can work with it like this.
If you're on Ben Console, just messenger, you'll see that there are a number of
commands here under the failed namespace. So let's say a failed show, check it out.
So instead of our failure transport, there are four messages just sitting in there
waiting for us to look at them and we can look at any of those individually. So we'll
also look at id one 15 here. You can see the error this because there's more
information here about like what the error was with the error class was with the
transport that originally was sent to and you get like a little message history. You
can see that it was fun, it failed and it was redelivered to the ASYNC transport at o
five and then I was six, then it oh seven it finally failed and was redelivered to
the fail failed transport.

If we do a dash vv on here, as it indicates there, we're actually going to see the
full stack trace of what happened on that exception. So this is a really powerful
way. So you configure out like what happened, do we have a bug in our application?
Um, or is this a temporary, the temporary failure. And then once you figured out what
to do with this, you have two options. You can either remove it by just saying remove
and that's going not going to retry it, it's just going to remove it or we can retry
it.

So let's actually go back to our handler here. I'm going to make go make this, go
back to failing randomly. Now when do you use this retry command? You have two
options. You can either retry a specific id like you see here or you can just retry
them one by one, which is actually what I'm going to do. So I'm gonna say Messenger
call on fail upon retry. And here it's going to go one by one. Here's the first one.
It says, it gives us all the details and it says, do you want to retry this? Yes or
no. And actually like before, if you pass this with a dash vv you're going to see the
full command details. So let's say yes and it processes and then asks us on the next
one. So actually let me try that again. I'm gonna do the dash VB cause that's going
to give us more information.

So you see it's the same thing. But now we have all the output. Let's say yes down
here you can see what's happening. Say, received that message. Well handle that
message and that message was handled successfully. So let's say yes again and this
time it fail. Remember that's something that can happen here. It's still failed
again. And the failure transport is just the same as any other transport. So what
does it do? And actually retries and sends back to the failure transport. So I'm
gonna hit control C and go back to our failed show originally. That id one 19 wasn't
there. That one 19 is actually that original message that failed and as being
retried. And so unless you change your configuration, otherwise your messages on here
are going to be retried three times before they are finally discarded from the
failure transport. And you can change it if you want to by changing your Max retries
two zero. There is a little bit of a bug right now on when something is a
[inaudible]. As you can see here, uh, the error in the error class are unknown.
There's still live inside the your transport, you can still look at that message
directly. Um, but we do need to fix that so it gets the right thing. But you can see
this full story here. You can see it was actually, uh, redrill it to the failure
transport a second time.

By the way, you can also pass, um, retry with a vast dash force and instead of asking
every time was just going to like try them one after another. So you can do that if
you have, a lot of them are some dumb plan with failures. Let's just go back now to
hand remove this code and make our image handler always work.