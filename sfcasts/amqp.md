# Amqp with RabbitMQ

Open up your `.env` file and check out our `MESSENGER_TRANSPORT_DSN` setting.
We've been using the doctrine transport type. The `doctrine://default` string
says that messages should be stored using Doctrine `default` connection. In
`config/packages/messenger.yaml`, we're referencing this environment variable
for both the `async` and `async_priority_high` transports.

So.. yep! We've been storing messages in a database table. It was quick to set up,
easy - because we already understand databases - and robust enough for *most*
use-cases.

## Hello AMQP... RabbitMQ

But the *industry* standard "queueing system" or "message broker" is *not* a
database tables, it's something called AMQP, or "Advanced Message Queuing Protocol".
AMQP is... not *itself* a technology... it's a "standard" for how a message broker
system should work. Then, different queuing systems can "implement" this standard.
Honestly, *usually* when someone talks about AMQP, they're talking about one specific
tool: RabbitMQ.

Here's the idea: in the same way that you launch a "database server" and make
queries to it, you can launch a "Rabbit MQ instance" then send messages to it
and receive messages from it. On a high level... it doesn't work much differently
than our simple database table: you put messages in... then ask for them later.

So... what are the *advantages* of using RabbitMQ instead of Doctrine? Maybe...
nothing? Well, what I mean is, if you *just* use standard Messenger features and
never dig deeper, both will work just fine. but if you have a highly-scaled system
or want to use some advanced RabbitMQ-specific features, well... then... RabbitMQ
is the answer!

What are those more advanced features? Well, stick with me over the next few chapters
and you'll start to find out.

## Launching an Instance via CloudAMQP.com

The easiest way to spin up a RabbitMQ instance is via `cloudamqp.com`: an awesome
service for cloud-based RabbitMQ... with a free tier so we can play around! After
logging in, create a new instance, give it a name, select any region... yep we
*do* want the free tier and... "Create instance".

## AMQP Transport Configuration

Cool! Click into the new instance to find... a beautiful AMQP connection string!
Copy that, go find our `.env` file... and paste over `doctrine://default`. You
can also put this into a `.env.local` file to avoid committing this string.

Anyways, the `amqp://` part activates the AMQP transport in Symfony... and the
rest of this contains a username, password and the other connection details.
As *soon* as we make this change, both our `async` and `async_priority_high`
transports... are now using RabbitMQ! That was easy!

Oh, but notice that I *am* still using `doctrine` for my *failure* transport...
and I'm going to keep that. The failure transport is a special type of transport...
and it turns out that the `doctrine` transport type actually has the *most*
features for reviewing failed messages. You *can* use AMQP for this, but I prefer
Doctrine.

Before we try this, I want to make *one* other change. Open up
`src/Controller/ImagePostController.php` and find the `create()` method: this is
the controller that's executed whenever we upload a photo... and it's responsible
for dispatching the `AddPonkaToImage` command. It *also* adds a 500 millisecond
delay via this stamp. Comment this out for now... I'll show you *why* we're doing
this a bit later.

## The AMQP PHP Extension

Ok! Other than removing that delay, *all* we've done is swap our transport config
from Doctrine to AMQP. Let's... see if things still work! First, make sure you
worker is *not* running... to begin with. Then, find your browser, select a photo
and... it worked! Well, hold in... because you *may* have gotten a *big* AJAX
error. If you did, open that profiler for that request... because I'm *pretty*
sure I know what error you'll see:

> Attempted to load class "AMQPConnection" from the global namespace.
> Did you forget a "use" statement?

Why... no we did not! Under the hood, Symfony's AMQP transport type uses a PHP
*extension* called... of course, amqp. It's an add-on to PHP - like xdebug or
pdo_mysql - that you'll *probably* need to install.

The *pain* with PHP extensions is that installing them can vary based on your
system. For Ubuntu, you may be able to run

```terminal
sudo apt-get install php-amqp
```

Or you might use pecl, like I did with my Homebrew Mac install:

```terminal
pecl install amqp
```

Once you've got it installed, make sure to restart the Symfony web server so that
it sees the changes. If you're having issues getting this configured, let us know
in the comments and we'll do our best to help!

Once it *is* all configured, you should be able upload a photo with *no* errors.
And... because this had no errors... it... *probably* just got sent to RabbitMQ?
When I refresh, it says "Ponka is napping"... because nothing has consumed our
message yet. Well, let's see what happens. Find your terminal and consume messages
from both of our transports:

```terminal
php bin/console messenger:consume -vv async_priority_high async
```

And... there it is! It received the message, handled it... and it's done! When
we refresh the page... there's Ponka! It worked! Switching from Doctrine to RabbitMQ
was as simple as changing our connection string.

Next, let's dig deeper into what just happened behind the scenes: what does it
mean to "send" a message to RabbitMQ or "get" a message from it? You're going
to *love* the debugging tools.
