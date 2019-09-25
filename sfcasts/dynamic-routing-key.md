# Dynamically Setting the AMQP Routing Key

So the cool
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
