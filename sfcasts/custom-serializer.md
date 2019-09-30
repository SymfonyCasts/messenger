# Custom Serializer for External Messages

Coming soon...

All right. So that's not really what we want. We want to do as effectively this same
thing except that we want to, um, except that the goal is that we're going to consume
from a transport. The goal is that we're going to consume from some transport. I want
that transport, it's going to read from Rabbit MQ. We'll take that data from
rabbit MQ and we'll turn it into a `LogEmoji` object and effectively dispatch it come
out that message. Bus Era dispatch right now. So the next step in getting this set up
is to create a, another transport. So I want to keep my `async` transport cause that's
going to be sending messages. And receiving messages. My `async_priority_high`, I'm
going to be sending and receiving, but I also want to create a new transport down
here. And the whole point of this transport is we're not going to send messages to
this transport. We're only going to consume messages from it. So I'm going to call it
`external_messages`. I'll use the same DSN because we're gonna still be consuming
things from rabbit MQ. Then I'll add options below here. Now the idea is instead of
consuming messages from `messages_high` are `messages_normal`, we're going to consume
them from a new uh, queue inside of here.

so I'm going to add queues

and then `messages_from_externals`, what we'll call it, a till that. Then above this, I
actually want you to add `auto_setup: false`. So there's like a few important things
happen here. The first thing is having this queue set up here says that when we
consume from this external messages, transport, it's gonna try to read messages from
a queue called `messages_from_external`. The second important thing here is I have
`auto_setup: false`, which probably makes sense because since we're expecting some external
system to send messages to this queue, we probably don't want or need messenger to
automatically create this queue for us. We're going to expect that it's already going to be
there. Also, you'll notice I don't have any exchange information down here. That's
also on purpose. The exchange is only needed when you're sending messages to a
transport. So the fact that we're never going to send a message to this transport
means that we do not need any exchange information. Now in reality, the exchange, uh,
is

no, I'm not going to cover that.

So with just this, we should be able to consume from this new transport. So spin over
and run

```terminal
php bin/console messenger:consume -vv external_messages
```

and it explodes. This is awesome.

> Server channel error: 404, message: NOT_FOUND - no queue 'messages_from_external'

So because we have the `auto_setup: false` instead of
creating that queue, it's just airing out on it. So let's go ahead over here and
let's create that by hand. So we'll call external messages. I'm not going to worry
too much about the arguments, but we will make it durable. Then hit add Q.

Now if we move over and run it again, oh, it's still air it out. Oopsies so scroll
up, you can see messages from external.

So move over. I'll copy that messages from external queue and then we'll go create that
by hand. Call it `messages_from_external`. Uh, won't worry too much about the options,
but we will make it durable. And there we go. We now have a queue. So if we spin back
over now and try it,

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

it works. It's consuming messages from that queue. Of course,
there's nothing in that queue. Um, but uh, but it's a reading from it. Alright, so
what are the messages going to look like? How can we kind of fake this? So here's the
idea. I'm going to pretend you can actually go into the rabbit MQ management and you
can use it to publish a message. So we can do this to kind of, um, pretend that we
are, uh, sending from an external system. So the key thing here is the payload.
That's the data that we're actually going to sentence the data that's gonna be
received. So what would it look like for an outside system to send a message to us
that says, we want to log in Emoji? Well, it might, let's just make something up. It
might look, we might decide, I want it looks like this. Uh, it's some JSON with an
Emoji key and they're gonna send us two. That'll be the index that they want us to
log. Cool. So let's publish this message.

And immediately if you look, we've over, our worker has exploded. Check this out,

> Could not decode message using PHP serialization:

And then it shows our JSON. So the
first big thing with an, if you're consuming messages that are coming from a third
party is they're probably not sending them in a PHP serialized format. In fact, they
shouldn't be. They're probably sending them as JSON or XML. So our transport is going
to, so our transport is going to need a custom serializer that's able to take this
JSON and turn it into that object. Let's do that next.