# Transport: Do Work Later (Async)

So far, we've separated the instructions of what we want to do - we want to add
Ponka to this `ImagePost` - from the logic that actually does that work. And...
it's a nice coding pattern: it's easy to test and if we need to add Ponka to an
image from anywhere else in our system, it's easy to do.

But this pattern unlocks some *serious* possibilities. Think about it: now that
we've isolate the *instructions* on what we want to do, instead of handling the
message object immediately, couldn't we, in theory, "save" that object somewhere,
then read and process it later? That's... basically how a queuing system works.
The *advantage* is that, depending on your setup, you could put less load on your
web server *and* give users a faster experience. Like, right now, when a user
clicks to upload a file, it takes a few seconds before it finally pops over here.
It's not the biggest deal, but it's not ideal. If we can fix that easily, why not?

## Hello Transports

In Messenger, the key to "saving work for later" is a system called transports.
Open up `config/packages/messenger.yaml`. See that `transports` key? The details
are actually configured in `.env`.

Here's the idea: we're going to say to Messenger:

> Hey! When I create an `AddPonkaToImage` object, instead of handling it immediately,
> I want you to *send* it somewhere else.

That "somewhere else" is a transport. And a transport is usually a "queue". If you're
new to queueing, the idea is refreshingly simple. A queue is an external system
that "holds" onto information in a big list. In our case, it will hold onto
serialized message objects. When we send it another message, it adds it to the
list. Later, when you're reading to process those messages, you can read them from
the queue one-by-one. When you successfully read them, the queue removes them from
the list.

Yea... it's really that simple.

## Transport Types

There are a *bunch* of queueing systems available, like RabbitMQ, Amazon SQS, Kafka,
etc. Out-of-the box, Messenger supports three: `amqp` - which basically means RabbitMQ,
but *technically* means any system that implements the "AMQP" spec - `doctrine`
and `redis`. AMQP is the most powerful... but unless you're already a queueing
pro and want to do something crazy, these all work exactly the same.

## Activating the doctrine Transport

Because I'm already using Doctrine in this project, let's use the `doctrine`
transport. Uncomment that key out under `transports`. See this `://default` part?
That tels the Doctrine transport that we want to use the `default` Doctrine connection.
Yep, it'll re-use the connection you've already set up in your app to store the
message inside a new table. More on that soon.

Now, back in `messenger.yaml`, uncomment this `async` transport, which uses that
`MESSENGER_TRANSPORT_DSN` environment variable we just created. The name - `async` -
isn't important - that could be anything. But, in a second, we'll *reference*
that name.

## Routing to Transports

At this point... yay! We've told Messenger that we have an `async` transport.
And if we want back and uploaded a file *now* it would... make absolutely no
difference: it would *still* be processed immediately. Why?

Because we need to
*tell* Messenger that our message should be sent to that transport, instead of
being handled right now.

Back in `messenger.yaml`, see this `routing` key? When we dispatch a message,
Messenger looks at *all* of the classes in this list... which is zero right now
if you don't count the comment... to see if it finds our class - `AddPonkaToImage`.
If it doesn't find the class, it handles the message immediately and... that's it!

Let's tell Messenger to *instead* send that to the `async` transport. Set
`App\Message\AddPonkaToImage:` to `async`.

As *soon* as we do that... it makes a *huge* difference. Watch how *fast* the
image loads on the right after uploading. Boom! That was faster than before and...
Ponka isn't there!

Actually, let's try one more - that first image was a *little* bit slow because
Symfony was rebuilding the cache. This one should be instant. It is! Instead of
calling our handler immediately, Messenger is *sending* our message to the
Doctrine transport.

## Seeing the Queued Message

And... um... what does that actually mean? Find your terminal... or whatever tool
you like to use to play with databases. I'll use the `mysql` client to connect
to the `messenger_tutorial` database. Inside, let's:

```terminal
SHOW TABLES;
```

Woh! We expected `migration_versions` and `image_post`... but suddenly we have a
*third* table called `messenger_messages`. Let's see what's in there:

> SELECT * FROM messenger_messages;

Nice! It has two rows for our *two* messages! Let's use the magic `\G` to format
this nicer. Nice! The `body` holds our object - it's been serialized using PHP's
`serialize()` function... though that can be configured. The object is wrapped
inside something called an `Envelope`... but inside... we can see our
`AddPonkaToImage` object and the `ImagePost` inside of that! Complete with the
filename, `createdAt` date, etc.

Wait... but where did this table come from? By default, if it's not there, Messenger
creates it for you. If you don't want that, there's a config option called
`auto_setup` to disable this - I'll show you how later. If you *did* disable
auto setup, you could then use the handy `setup-transports` command on deploy
to create that table for you.

```terminal-silent
php bin/console messenger:setup-transports
```

This doesn't do anything now... because the table is already there.

Hey! This was a *huge* step! Whenever we upload images... there are *not* being
handled immediately: they're being sent to Doctrine and *it* is keeping track of
them. Next, it's time to *read* those messages one-by-one and start handling them.
We do that with a console command called a "worker".
