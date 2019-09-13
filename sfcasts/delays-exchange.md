# Delays Exchange

Coming soon...

When we started working with AQP QP, I had you go into image post controller and
remove the delay stamp. Remember this tells the, uh, the, the, this tells your
transport system to delay this message for 500 milliseconds before allowing a worker
to receive it. Let's change this to 10 seconds. So 10,000 at milliseconds. Then go
over and make sure that your worker is not working currently. And let's go try this
right now. You can see that our [inaudible] are both empty. So let's go over it.

Okay,

upload three photos and quick go back, look at cues. And we have a new Hugh with a
very strange name, delay messages, high priority_to score 10,000 with three messages
in it and it has a whole bunch of features on it. And if you look inside of here, it
actually looks like the messages were delivered here. Interesting. Well, but then
look at that. They're gone. We had three cute messages and now they're gone. And
suddenly,

okay,

that queue is actually four oh 14

what?

And the messages are in messages under score high. What the heck just happened? Well,
first of all to prove that, uh, this all actually did work. If we consume from our
aceing priority ACI pretty high and Async, you can see it is actually consuming those
messages messages right now and go over to the homepage and refresh and you can see
yet Punko was added to those images. So delaying and delaying messages in, in Rabbit
MQ is actually kind of tricky behind the scenes and it exposes some really cool,
interesting features about how aim QP works.

Okay.

So first I'm going to go over and upload three more photos. Garver here, are you
fresh? Once again, see that cue there and then I'm actually going to turn my wifi off
because I don't want this page to suddenly disappear. I want to look at it cause it
has some really interesting things. So as soon as you have a delay on your message,
the first thing that happens that messenger does not send to the normal exchange

[inaudible]. Okay, cool. Cool, cool, cool, cool, cool.

I'm going to go back and re up all those photos. Actually before we upload those
photos, I want you to go do exchanges and notice there's a new one here called
delays, and this is not a a a fanout anymore, and this is actually a direct type
which we're gonna talk about. So the first thing to know is as soon as your message
has a delay on it, instead of going to the correct exchange, it's actually sent to
the delays exchange. And you'll see right now that this has temporarily hasn't no
bindings on it to make this all easier to see. I'm actually going to temporarily make
this delay even higher up to 60 seconds.

All right, now let's go over and now let's upload three photos. Those are instantly
going to be sent to the delay exchange. And if I refresh this page here, you can see
it as a new binding that says if there's a routing key called delay messages, high
priority under scorners score 60,000 then it's sent to this particular queue. A
router key is a property that you can set on the message. When it's deliberate, it's
a little flag that helps the exchange figure out where it's going to go. So all those
messages were sent, messengers sent of that routing key, which means that it was sent
it to this queue. Now this queue is really, really interesting because notice it has
a couple of important properties here. First has an x message t t l of 60 seconds
that actually says after a message has lived in here for 60 seconds, remove it from
this queue, which seems crazy, right? Why would we want messages only to live for 60
seconds? Well, it's by design because the second thing here, the x dead letter
exchange, that is a fancy key that says if a message is removed from this queue
because it was sitting here because it hit the TTL, you should actually send it to
the message messages, high, high priority exchange. So it's a little trick going to
send messages to this queue, tell the messages in this queue to expire after 60
seconds and after 60 seconds to send it to the correct exchange,

okay?

And Boom, you can see as soon as it does that the Q is actually marked as a temporary
cue. So it actually sends those messages and deletes itself. Now if I click back on
cues, those messages have been sent to the messages hi, exchange back to the messages
IQ. And over here you can see it actually, uh, it actually consumed those. Wow.
Right? So the key takeaway here is that, um, this, you don't really need to know and
understand like how the delay is working behind the scenes. But this is a really nice
way to see some of the, a kind of more advanced power game CUPE and a couple of the
properties that you can set when you're setting messages. The real star of this show
just also was the first time that we saw an exchange that was not fan out, but was
actually a direct and relied on routing keys to figure out what that this message
should go to this queue. And this message. Did you go to the barbecue? You notice you
can't see any bindings any more? Because I'm the, those delay cues are temporary. So
as soon as at the lake, you have no messages in it, it deletes itself and it deletes
the binding board to talk more about those routing, uh, routing keys and bindings.
Um, next.

Okay.