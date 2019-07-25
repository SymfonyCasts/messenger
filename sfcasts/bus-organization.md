# Bus Organization

Coming soon...

We're now using both a command bus pattern where we create commands and command
handlers. And also we have our first event and event handler. And remember the
difference between a command and event. It's really kind of subtle. A command usually
has exactly one handler and um, and it's commanded to perform some action, whereas an
event is something that's usually dispatched after that action is taken and it allows
for anyone else to do any others. Secondary tasks often called a reaction.

Okay,

so messengers, messengers, um, but a message bus can really be used by either of
these two different patterns. Now in config packages, messenger.yaml we actually
registered a, a bus that we're using as our command bus and a bus that we're using as
our event bus. But really there's almost no difference between these two buses. Sure.
This one has the audit middleware, but honestly we could add the audit middleware
down here too. That'd be fine. Really the only difference between these two is that
this one allows no handlers. So you can have an event that doesn't have any handlers
and it wouldn't throw an exception. So the point is I've created two different buses
to handle these two different things. But really if you wanted to, you could just
create one bus and dispatch both commands and events to it. It wouldn't be the
craziest thing. Some people actually add a couple of additional pieces of middleware
to um, to the buses

specifically if you Google before Symfony Messenger, multiple buses, we have a little
article in here that talks about how to manage multiple buses and in this case they
actually show three different buses. That command bus a query bus, which we'll talk
about in a second, in a, in an event bus where each actually has slightly different
middleware. Um, I wanted to highlight this because this validation middleware and
this doctrine transaction middleware we haven't talked about yet. The validation
middleware actually allows, um, if you enable that, then when you dispatch a message
into your, uh, bus, it will actually pass through Symfony's validator and it will
throw an exception and validation fails. Some people like to actually put validation
inside their bus. I prefer to actually perform validation on my message, on my data
before I sent in the bus. But this is something you can do. Doctrine transactions,
another one where instead of manually, uh, managing doctrine transactions, uh, you
add this middleware and everything is automatically inside of a doctrine transaction.

Okay.

I also don't do that because I just manage the doctrine transaction myself, but I
wanted to highlight it. So in this case,

okay,

if you are using these middlewares, some of these different types of buses use
different middleware, but if you don't use those really the buses are very, very
similar so you can merge them into one if you wanted or if you want to, we can have
multiple buses.

Okay.

I'm going to keep

things organized into multiple buses even though, honestly that doesn't give me a lot
of advantage. But if you had ever run bin Console, debug messenger, you actually see
here that it actually breaks down the information by bus. It says that the following
messages can be dispatched to the Ma, uh, our command bus and these same messages are
allowed to be dispatched to the event bus. That's because when we set up our our
handlers, and we never said that this type of a, this command should, can only be
dispatched to the command bus or this event can only just be dispatched to the event
bus. If we accidentally took this command here and send it to the event bus, it would
work. If we took this event in x, send it to the command bus, it would also work. And
I [inaudible]

and honestly that's also fine. But we're going to do a little bit of experimenting
here and we're going to get things just a little bit more organized. So first of all,
just looking at our message and messages handler class, we now have a mixture in here
of events and commands. I put the event into an event subdirectory, which is kind of
Nice. I'm not going to do the same thing with a command. So I'm gonna create a
command sub-directory move my two commands in there and then just refactor a couple
things. So I'll add /command to the end of the namespace for each of those. Then I
need, just need update a couple of parts of the code. One of them is in
Messenger.yaml. We're referencing the ad pocket image. Should we need to add the
command namespace there and then in our controller image posts controller all the way
on top, we are referencing both of those commands because this is where we dispatch
them.

Then finally in the handlers themselves, of course we have the same thing. We have
used statements here that reference those command classes. So we'll add the command
names based on both of those and then we'll do the same thing for the message
handlers. I'll put those into a subdirectory called command. Move those in there, and
then I'll just manually add a command at sub namespace on there. So if you know that
you're going to be using a command handler and a command boss and an event bus and
maybe a query bus, um, this is a nice way to organize things, but it's basically
superficial. Everything's going to work exactly the same way that it did before. And
if you go over and run diva messenger,

okay,

you'll see the same results.

Okay.

But one of the things you can do is you actually can, if you want to, you can raise,
you can actually can add extra metadata that says that a specific, um, handler can
only be called by a specific bus. You can actually tighten this up a little bit and
it's again, not, maybe not that important to do, but it is kind of a fun exercise to
check out. So openconfig p a config services. Dot. Yammel.

Okay.

So this line right here is what's responsible for auto registering all of this, all
of our classes into the container. Yeah.

And this

part down here, um, does the same thing overrides the services for the controllers.
They get this extra argument, not that important what we're doing, but we can
actually do something similar here. We can say app, message, handler, slash, command.
And we're going to point that at just the command directory. So that dot. /source
/message handler. Slash. Command.

Okay.

Now, if we just stopped here, um, this wouldn't do anything. This would basically
reregistered. Uh, and this would basically register everything in this directory as a
service, but that's already done by this first app entry anyways, but now we can add
a tag to this

whose name is messenger dot message handler. And then here I'm going to say bus and
I'm going to use the name of my bus from Messenger at Yamhill. So I'll copy that
messenger that bus dot defaults and say bust the Messenger that bust out the fault.
So there's a couple of things going on here. First of all, internally automatically
in Symfony, if your class implements message handler interface, then normally this
messenger, that message handle or tag is automatically added, which is how somebody
knows that this is a a message handler. But if you want to, you can actually
reregister the service yourself. And if basically override that tag and we're doing
it here is we're overriding that tag and we're actually adding this messenger dot
bust dot default thing on there. Now I'm also going to add one other thing here,
which is auto configure false auto configure, thanks to the_defaults up here. Auto
configures a feature that's on by default for all of the services. So this turns auto
configure false offer any services that are from this namespace. The reason that's
important is it will avoid the this tag being applied twice.

So the end results of this is actually if you're on debug messenger again, oh, I,
you'll actually get in here because I have a mistake. Oh, I forgot my arm handler. So
if you run debug messenger again, you'll actually see that, uh, the event bus here
suddenly does not advertise that we can dispatch the two commands to it. And that's
because both of these handlers now are tied specifically to this bus.

Okay?

So I can actually do the same thing here. I'm gonna copy this entire section.

Okay?

Change the namespace to event the directory to event. And here I'm going to put boss
event that bus the name of our other bus inside of services.yaml and now I'll
complete the a kind of locking down things. So if I do debug messenger again,

you

can see that our two command handlers are tied to our command bus and our one event
handler is tied to our event bus. So if we accidentally dispatch an event,

okay,

a, an event to the command bus, it's not

okay.

So it's really not that important of a step, but it does kind of tighten things up a
little bit.

Okay.

And most importantly, this was a nice way for us to get these nice subdirectories in
here of command and event. Now, while I'm here, uh, back in config packages,
messenger, not Yammel our main buses called messenger that bust out the faults, this
becomes the service ID in the container. And we did that, we used it that one because
that's just the default value normally. But I'm actually gonna make things a little
bit nicer here by just changing that to command that bus. And then up here, I will
use that as our default bus. And actually the only place that that's referenced in
our code is actually in services, that Yam one, which we just created. So I'll change
that to command that bus as well. It's not if you're on debug messenger, it's a
really clean set up. We have two buses. They're servers had, these are command bus
and event bus in both of them. Only a really worry about the handlers, uh, for their
specific type. And also, while I'm here, as I mentioned earlier, and this autumn
middleware is something that we could also apply to our event bus. Um, something that
just adds logging, so why not add it there as well.

So if you do want a little bit more organization between your messages and events,
you can totally do this. If this seemed like a lot to you, just keep one bus and put
both of your events and your commands into that one bus, that's honestly going to
work just fine. Yeah.