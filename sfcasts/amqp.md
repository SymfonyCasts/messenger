# Amqp

Coming soon...

Open up your `.env` file you see we've been so far. If you look at the 
`MESSENGER_TRANSPORT_DSN`, we're using the doctrine transport type `doctrine://default` says
that we should use the default doctrine connection. So basically it means we're
storing all of our messages in a database table and you'd see this `MESSENGER_TRANSPORT_DSN` 
is something that we reference for both their `async` and `async_priority_high`
So doctrine is the probably the easiest way to store messages cause most of us
are familiar with doctrine, we're familiar with databases. And so this idea of just
having a table in the database, um, is very straight forward. However, the more
industry standard, um, transport type, it's probably something called AMQP. AMQP
is a, um, kind of a stick is a standard and usually when you hear AMQP or actually
talk about is rabbit MQ.

So instead of a database, you actually have a, a rabbit MQ instance, um, deployed
somewhere and you actually communicate back to it. You send in messages and you
consume messages from it. So what's better doctrine or rabbit MQ? Well, the answer is
rabbit MQ, but only if you are going to need some of the more advanced features of
Rabbit MQ. What are those more advanced features? Well, stick with me and I'll show
you a little bit of how rabbit and computers and a little bit of what those more
advanced features are. So the easiest way to spin up an instance of rabbit MQ is
actually good to go to `cloudamqp.com` this is an awesome little service where you
can get rabbit MQ is a service and it has a free tier for your play around with. So
I'm go ahead and lock in off, create a new instance, give it a name, slack. My region
doesn't really matter confronting that, we want the free tier and then we'll create
that instance. Now if we click edit next to this,

now if we click into the Symfony cast tutorial link here, awesome. It gives us a nice
AMQP url that we can use. So I'm going to copy that

then I'm gonna go into my `.env` file.

And paste that over the `doctrine://default`. So the AMQP Bart tells us to use
the AMQP transport type and then it has kind of a uh, all the connections specific
details right here. If you want, you can put this into `.env.local` file. If you
don't want to commit it, that's what I would usually do. Putting it in the `.env`
for simplicity. Now notice as soon as we change this, this means that our `async` and
`async_priority_high` transports, we'll be using AMQP. I just want to make a note
that our failed transport, I hard coded to use doctrine. I'm actually going to keep
doing that. The failed transport when it comes to the failed transport doctrine
actually has the most features out of all the transports. Field transports, kind of
this weird guy. So I'd recommend keeping your failed transport as doctrine

And then one other thing before we start testing this, why don't you to go into your
`src/Controller/ImagePostController.php`. And if you look at the `create()` method here,
this is the end point that's executed. Whenever we upload a photo and it dispatches
the ad, Ponca do image and actually adds a 500 millisecond delay. I want you to
temporarily comment out that delay and I'll show you why in a few minutes. But that
will actually, that will complicate things unnecessarily. All right, so let's try
this. We've just, I've done is changed the backend for our, our queuing system. So
first go find and make sure that you weren't worker is stopped just to keep things
nice and simple. Then I'm gonna go over

Find an image, upload it, and it worked. Now be careful here because it's very
possible that it did not work for you. And you're seeing a big air over here and down
here, a big Red->there on that case. If you want to, you can open it up and go to the
uh, exception tab here to see what, what happened. But the error you're probably
getting is a AMQP connection class is not found. In order to use, uh, AMQP You
need a PHP extension called AMQP. You can install this if you are on a bun too with
apt-get install somethings I've missed something. Or if you are on using brew like
me, uh, you can assault with pecl install amqp. So make sure you get that
installed. And if you did get this air, you will need to go back and uh, go over to
your Symfony server and make sure you restart your server after you install it so
that it sees it. So once you get installed, go back and try to upload a photo until
you actually get it working. Now as far as I know, this prop hopefully just got sent
over to amtp. You can see when I refresh, it says Pona is napping. So let's see if
we can consume it. I've got it. I'll go back to my tap here and we'll consume as
usual from both our async priority high and ASYNC transports. 

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

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