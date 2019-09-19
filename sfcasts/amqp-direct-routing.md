# Amqp Direct Routing

Coming soon...

Let's change this delay back to just one second. So we're not waiting all day for our
uploaded photos to go. All right, so in order to power our two different transports
here, `async` and `async_priority_high`, they end goal is always that we need messages
sent to each of those to go into two different queues. And we've accomplished that.
If you look at queues, we have a `messages` and the `messages_high` queue, there are two
different ways to accomplish that though. Remember messages are always sent to an
exchange. So right now what we're doing is we're sending the `async`, uh, when you
route things to the `async` transport, they're always sending two a exchange called
`messages`. You don't see that config here, but the default exchange is `messages`. If
you route a message to the `async_priority_high` transport, it's sending things to
the `messages_high_priority` Exchange, switch transport is, is linked to exactly one
exchange.

And then each exchange sends all of its messages to a single queue like the `messages`
queue or the um, messages prior and high priority exchange sends to the `messages_high`
cue. So that's one way to to solve this problem. The other way to solve this problem
is to only have a single exchange and then make that exchange smart enough to send a
certain messages to queue A and other messages to queue B. We do that with something called a
binding and routing keys, which we've already seen a little bit of. So that's what I
want to do. I want to refactor our transports to be a little bit smarter. So let's
start with the first transport. I'm going to add `options` below here and then say
`exchange` and then say `name: messages`. Now we stop right there. This is the default
configuration, so that doesn't change anything.

The first important thing we're gonna do is we're going to change the `type` two
`direct`. So instead of the fan out type, which says send it to all of the queues that
are bound to this exchange, this is going to be direct means that you actually need
to tell the exchange via a routing key exactly which queue or queues. You want that to go
to? You'll see that in a second. Now I'm also going to add below something called
`default_publish_routing_key` set to `normal`. I'll talk about that in a second. Below
this I'm also going to add a `queues` scheme and I'm going to declare a queue called
`messages_normal`. And under here I'm going to say `binding_keys: normal`. So [inaudible]
which is the same as I'm using for the `default_publish_routing_key`. Now before
we talk too much about this, let's see what this actually ends up doing inside of
AMQP.

So, uh, the `async` to get a message sent to the async, uh, transport, let's delete one
of our messages here and Oh, you can see it exploded 500 error. So I'll open that up
here. And it says 

> Server channel error: 406, message: PRECONDITION_FAILED - inequivalent arg 'type'
for exchange 'messages': received 'direct' but current is 'fanout' 

So the problem is that we already have an ex a fan, aren't we already have
an exchange called `messages`, which they `fanout` type. And now we're trying to change
it to a `direct`, that's not something that you can do. What we really want to do is
actually start over. We want to say, hold on a second, we're doing this a new way.
Let's just delete everything, all of our exchanges and all of our cues so that it can
be restarted from scratch. So I'm gonna go down to the messages exchange and I'm
going to delete the messages. Exchange will delete messages high priority cause we're
not even gonna use that anymore. And just to be extra cool, let's go ahead and delete
both of our queues as well. So I'm gonna delete the messages cue and also delete the
messages. Hi Queue. So we're back down to no cues and uh, and no exchanges except for
the ones that aim QPA aim QPR by default, which you could tweet


and oh, our two two exchanges are back. I'm an idiot. All right. Going back to what
we really want to do is start over. We really want to do is start over and delete all
of our exchanges in all of our cues in just, and just start from scratch. So first
let's move over and I'll actually log out of my database. But most importantly, stomp
your consumer. Now I'm gonna go back over and let's just delete the messages,
exchange, delete, and then I'm going to delete the messages. High Priority Exchange,
that's not one we're even gonna use anymore. And then just for good measure and cues,
we'll delete the messages cue and the messages. High Cue. So at this point we have no
queues and we have the built in exchanges that AMQ p added, which we're not even
using. And we have the delays exchange, which you could also delete if you want to,
but it's not hurting anything. So we are starting from scratch. All right, so now
let's go and let's try to delete him as image again. And this time, oh four a four
because I already deleted that one for the database.


And let's delete an image and perfect. It worked. All right, checkout what happened
inside of Rabbit MQ. Now we have a new exchange called `messages`, which is a `direct`
exchange and inside of here it has checked it up bindings. It is a single binding. It
says, when a message is sent to this exchange with a routing key called `normal`, it
will get sent to the queue called the `messages_normal`.

This was all set up thanks to the binding key stuff. This says, create a queue called
`messages_normal` and make sure that it's, it has a binding key in this exchange that
says a routing key of `normal` will get sent to this queue. Now thanks to this 
`default_published_routing_key`. This says all, whenever we, uh, route a message to the `async`
transport, it will be sent to the `messages` exchange and we'll automatically have a
`normal` routing key, which means that if you go to queues, we do have a `message_normal`
queue, and it has our one message sitting inside of there. So the cool
thing now is we can repeat this down here. For our `async_priority_high`, we're gonna
change the exchange, just `messages` so it matches what's above. Then we will use the
same type `direct` and this kind of, I'm going to say `default_publish_routing_key`.
And we'll use something called `high`

and below for `messages_high`, I don't want to just declare that queue. I wanted to clear
that queue, but I also want to add a binding key for it. So `binding_keys: [high]`.

So to kind of trigger that, I'm going to go over this time and I'm going to upload a
photo. Once I do, we'll go back over to our manager here. And the first thing I'm
gonna do is look at the exchanges.

we still just have that one messages exchange. But now as to binding keys, if you
send a message with a `high` routing key, it's going to go to a different queue. So if
we go to queues now you can see that our message is high, has one message, and
our other cue has just one message in it. So now because this direct thing and these
routing keys, we can send messages directly to each transport directly to each to one
exchange, and then it's routed to different queues. This is really how AMQP is
supposed to be used. Now if I go over and consume the messages, we'll consume 
`async_priority_high` than `async`. 

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

It's gonna consume them in the correct order. I mean it's
going to handle the `AddPonkaToImage` first because that's on the high priority queue
and then it's going to handle the image post, delete it afterwards. Now when you do
this, you have one other kind of cool thing that can do.

Normally, um, you a route a class like `AddPonkaToImage` to a specific transport.
So `AddPonkaToImage` is always going to go to `async_priority_high` and when you go to
`async_priority_high`, it's gonna publish with a routing key of `high`, which is going to
end up putting it into the `messages_high` queue. If you can follow the logic there.
Once you can't do normally as you can't dynamically say that in some situations I
want `AddPonkaToImage` to go to one transport and then other situations I
want to go do another transport. That's just not possible because you always route
things based on the class name. But by taking advantage of routing keys, you can like
what if we can publish an `AddPonkaToImage` but then tell messenger that when it
sends it to this exchange to use the routing key called `normal`, that would mean it
would end up inside of the `messages_normal` queue. And we actually can do that in
`ImagePostController`. We can do that with a stamp. So after the `DelayStamp`, I'm
going to say new, `AmqpStamp`, not received stamp on and few stamp mistakes in three
arguments. The first one is called routing key. So we can say `normal`.

So to go over I'm going to stop and my worker so we can see what happens here.
then we'll go over, upload a photo and go check out the cues and refresh,
wait for the delay to finish and there it is, it ended up and `messages_normal` queue.
So that's a really cool way that we can actually take more control over how things
are routed. By the way, if you look inside this `AmqpStamp` thing, the second
argument are things called it flags and attributes, which are a little bit more
advanced. But if you, um, I'll Shift + Shift and I might open up a class called
`connection.php`. It opened up the AMQP extension connection class, then a search
for a message here called publish on exchange.

And then ultimately I'm doing as I'm just skipping to an internal class. Um,
eventually if you do set the flags or attributes on that stamp, they are passed as
these third and forth arguments to this `$exchange->publish()` that exchange your
publish. If I hold that, this is actually the core, AMQP extension, sorry.
[inaudible] aim could be exchange class. And this is [inaudible] `publish()` method and
up here at documents that kind of flags and attributes you can send there. So you can
read more about the AMQP published method. If you need to do something a little bit
more custom, you can attach it to the stamp. All right, so that's, that frame could
be by [inaudible].