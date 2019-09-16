# AMQP Internals: Exchanges & Queues

Coming soon...

Hit it and there it is.
Received the message in, handled the message, and it's done. Sure enough, if we
refresh back over here, boom, Ponka is in our image.

So let's look a little bit more on how this works. I want you to stop the worker and
then let's go over and delete a few images. Images, how one, two, three. So we just
deleted three images and that should've caused three messages to be sent to uh, to
rabbit MQ to AMQP. One of the really cool things about rabbit MQ is that it has
this cool thing called Rabbit MQ manager. So I'm gonna Click that and this gives you
really, really good, um, view into what's going on inside of your rabbit MQ instance.
So the first and foremost, it's this idea of exchanges. So exchanges are what you
send messages to and all of these exchanges here were automatically created for us so
you can ignore them except for `messages` that was created by our application. And it's
what Messenger is sending all of its messages to. You don't see it in our
configuration here, but each transport has an `exchange` option. We'll see it in a few
minutes and then defaults to `messages`.

Now you see the type here, it says as a type called `fanout`. We're gonna talk a
little bit more about that in a little bit, but basically messenger sends the
messages to this messages exchange, and if I click on it and open up bindings, you
can see it as a binding to a queue called messages. So this can be a little bit
confusing. The messages are sent out to an exchange and then the exchange has these
rules called bindings that basically say, under this condition, this message should
go to this QA under this other condition. As you go to QB or under this other
condition that should go to QC. We're gonna talk more about how those conditions,
they're called routing keys and a little bit, but by default it's very, very simple
because this is a fanout exchange. It basically means every single message that gets
sent that gets sent to this exchange goes to all of the cues that it's bound to.

And by default it's only bound to one. So this is a very overkilled way of saying
that Messenger is going to, is currently sending all of its messages to the messages
exchange, which causes all of them to go to this queue called messages. What you
going to click on here to get, or you can click on queues on top. And yes, you can
see we have exactly one key running right now called `messages`. And if you look
inside, look ready `3`, it has three messages inside of it are three delete
messages that were sent. By the way, we didn't have to go into rabbit MQ and create
this messages exchange and this binding and we didn't have to create this message as
cute. And that's because like with the doctrine transport, the AMQP
transport is auto set up. By default it means that it will detect if the exchange and
the cues are that it needs are there and if they're not, it's going to automatically
create them. So it took care of creating the exchange, creating the queue, um, and, and
tying them together for us.

so the key concept in AMQP is exchanges in queues. And the key concept is that you send to
exchanges. So are when we deleted a second ago, these 3 messages were sent to
this exchange. Then it follows. Then based on the rules of that exchange, those
messages end up in one or more queues and they just sit there. The second part of the
equation is your consumer, your worker, your worker doesn't know anything about
exchanges. It consumes from queues

so when we execute our your worker, it's going to be asking Rabbit MQ, please give me
the messages from the `messages` queue

Now before we actually do that, let's upload 4 photos and then if we go over here
in quicken, this messages, you can see there's three messages right now, but if I hit
refresh year in a second, messages that boom, you can see it popped up to 7
messages. So our original three plus our four and there not four messages waiting in
that queue.

so let's go consume them. Now as a reminder, before we consume, we're sending the
`AddPonkaToImage` to `async_priority_high` and the delete to `async`. The idea was that we
with their other own that puts them in sort of two buckets and we can read them
independently. You can already see a problem with our setup right here cause that's
putting all of those messages into the same bucket. And we're not, we don't have two
queues right now. We ended up with just one queue with everything mixed together. And
in fact if we go over here and just consume the `async` transport, that should cause us
just to uh, consume `ImagePostDeletedEvent`s. But when we run it,

```terminal-silent
php bin/console messenger:consume -vv async
```

Yup, it does handle `ImagePostDeletedEvent`s.

But if we keep watching eventually you can see it actually finishes those and it
starts with the `AddPonkaToImage`

so we have such a simple setup right now that we've actually introduced a bug into
our application where our two types of transports are actually sending to the exact
same place, uh, which kills our, um, our priority feature. So we need to fix that
next. But if you look back over on Rabbit MQ Admin, it actually is pretty cool. You
can see all the messages getting kind of consumed out of it. All right, so let's
figure out what's going on with that, et Cetera, et cetera. Next.
