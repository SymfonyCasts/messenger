# Query Bus

The last type of message bus that you'll hear about is... the double-decker tourist
bus! Um... the query bus! But... full disclosure... I'm not a huge fan of query
buses: I think it makes your code a bit too complex for little benefit. That being
said, I want you to *at least* understand what it is and how it fits into the
message bus methodology.

## Creating the Query Bus

In `config/packages/messenger.yaml` we have `command.bus` and `event.bus`. Let's
add `query.bus`. I'll keep things simple and just set this to `~` to get the
default settings.

## What is a Query?

Ok: so what *is* the point of a "query bus". We understand the purpose of commands:
we dispatch messages that *sound* like commands: `AddPonkaToImage` or
`DeleteImagePost`. Each command has exactly one handler that performs that work...
but doesn't *return* anything. I haven't really mentioned that yet: commands *just*
do work, but they *don't* communicate anything *back*. Because of this, it's ok
to process commands synchronously or asynchronously - our code isn't waiting to
get information back from the handler.

A query bus is the *opposite*. Instead of commanding the bus to do work, the point
of a *query* is to get information back fro the handler. For example, let's pretend
that, on our homepage, we want to print the number of photos that have been
uploaded. That is a *question* or *query* that we want to ask our system:

> How many photos are in the database?

If you're using the query bus pattern, instead of getting that info directly,
you'll dispatch a *query*.

## Creating the Query & Handler

Inside the `Message/` directory, create a new `Query/` subdirectory. And inside
of that, create a new PHP class called `GetTotalImageCount`.

Even that *name* sounds like a query instead of a command: I want to get the
total image count. And... we can leave the query class blank: we won't need to
pass any data to the handler - we're just asking this simple question.

Next, inside of `MessageHandler/`, do the same thing: add a `Query/` subdirectory
and then a new class called `GetTotalImageCountHandler`. And like with *everything*
else, make this implement `MessageHandlerInterface` and create the
`public function __invoke()` with the message type-hinted argument:
`GetTotalImageCount $getTotalImageCount`.

What do we do inside here? Find the image count - probably by injecting the
`ImagePostRepository` and executing a query - and then *return* that value. I'll
leave the querying part to you and just `return 50`.

But hold on a second... cause we just did something *totally* new! We're returning
a value from our handler! This is *not* something that we've done *anywhere* else.
Commands do work but *don't* return any value. A query doesn't really do any work,
its *only* point is to return a value. That's the difference between a query and
a command.

Before we dispatch the query, open up `config/packages/services.yaml` so we can
do our same trick of binding each handler to the correct bus. Copy the `Event\`
section, paste, change `Event` to `Query` in both places... then set the bus
to `query.bus`.

Love it! Let's check our work by running:

```terminal
php bin/console debug:messenger
```

Yep! `query.bus` has one handler, `event.bus` has one and `command.bus` has
these two.

## Dispatching the Message

Let's do this! Open up `src/Controller/MainController.php`. This renders the
homepage and so *this* is where we need to know how many photos have been uploaded.
To get the query bus, we need to know which type-hint & argument name combination
to use. We get that info from running:

```terminal
php bin/console debug:autowiring mess
```

We can get the main `command.bus` by using the `MessageBusInterface` type-hint
with *any* argument name. To get the query bus, the type-hint *and* the argument
needs to be called `$queryBus`.

Do that: `MessageBusInterface $queryBus`. Inside the function, say
`$envelope = $queryBus->dispatch(new GetTotalImageCount())`.

We haven't used it too much, but the `dispatch()` method *returns* the final
Envelope object, which will have a number of different stamps on it. One of the
properties of a *query* bus is that every query will *always* be handled synchronously.
Why? Simple: we need the answer to our query *right* now - so our handlers need
to run *immediately*. In Messenger, there's nothing that *enforces* this on a
query bus... it's just that we won't ever route our queries to a transport, so
they'll always be handled right now.

Anyways, once a message is handle, it will always have a `HandledStamp` on it.
Let's get that: `$handled = $envelope->last()` with `HandledStamp::class`. I'll
add some inline documentation above that to tell my editor that this will be a
`HandledStamp` instance.

Why are we reading this stamp? We need to know the *return* value of our handler.
And, conveniently, *that* is stored inside of this stamp. We can say
`$imageCount = $handled->getResult()`.

Let's pass that into the template as an `imageCount` variable.... and then in the
template - `templates/main/homepage.html.twig` - because our entire frontend is
built in Vue.js, let's override the `title` block on the page and use it there:
`Ponka'd {{ imageCount }} Photos`.

Let's check it out! Move over, refresh and... it works! We've Ponka's 50 photos...
at least according to our hardcoxed logic.

So... that's a query bus! It's not my favorite because we're not guaranteed
what *type* it returns - the `imageCount` could really be a string... or an object.
Because we're not calling a *direct* method, the data we'll get back feels a little
fuzzy. Plus, because queries need to be handled synchronously, you're not saving
any performance with this: it's purely a pattern.

But, this is *totally* subjective, and a lot of people love query buses. In fact,
we've been talking mostly about the *tools* themselves: command, event & query
buses. But there are some deeper patterns like CQRS or event sourcing that these
tools can unlock. This is not something we currently use here on SymfonyCasts...
but if you're interested, you can read more about this topic -
[Matthias Noback's blog](https://matthiasnoback.nl/) is my favorite source.

Oh, and before I forget, if you look back on the Symfony docs... back on the
main messenger page... all the way at the bottom... there's a spot here a about
getting results from your handler. It shows some shortcuts that you can use to
more easily get the value from the bus.
