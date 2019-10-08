# Query Bus

The last type of bus that you'll hear about is... the double-decker tourist
bus! I mean... the query bus! Full disclosure... while I *am* a fan of waving
like an idiot on the top-level of a tourist bus, I'm *not* a huge fan of query buses:
I think they make your code a bit more complex... for not much benefit. That being
said, I want you to *at least* understand what it is and how it fits into the
message bus methodology.

## Creating the Query Bus

In `config/packages/messenger.yaml` we have `command.bus` and `event.bus`. Let's
add `query.bus`. I'll keep things simple and just set this to `~` to get the
default settings.

[[[ code('602f5d0add') ]]]

## What is a Query?

Ok: so what *is* the point of a "query bus"? We understand the purpose of commands:
we dispatch messages that *sound* like commands: `AddPonkaToImage` or
`DeleteImagePost`. Each command then has exactly one handler that performs that work...
but doesn't *return* anything. I haven't really mentioned that yet: commands *just*
do work, but they *don't* communicate anything *back*. Because of this, it's ok
to process commands synchronously or asynchronously - our code isn't waiting to
get information back from the handler.

A query bus is the *opposite*. Instead of commanding the bus to do work, the point
of a *query* is to get information back *from* the handler. For example, let's pretend
that, on our homepage, we want to print the number of photos that have been
uploaded. This is a *question* or *query* that we want to ask our system:

> How many photos are in the database?

If you're using the query bus pattern, instead of getting that info directly,
you'll dispatch a *query*.

## Creating the Query & Handler

Inside the `Message/` directory, create a new `Query/` subdirectory. And inside
of that, create a new PHP class called `GetTotalImageCount`.

Even that *name* sounds like a query instead of a command: I want to get the
total image count. And... in this case, we can leave the query class blank: we
won't need to pass any extra data to the handler.

[[[ code('e1efceb84f') ]]]

Next, inside of `MessageHandler/`, do the same thing: add a `Query/` subdirectory
and then a new class called `GetTotalImageCountHandler`. And like with *everything*
else, make this implement `MessageHandlerInterface` and create
`public function __invoke()` with an argument type-hinted with the message class:
`GetTotalImageCount $getTotalImageCount`.

[[[ code('a8fd7e470f') ]]]

What do we do inside of here? Find the image count! Probably by injecting the
`ImagePostRepository`, executing a query and then *returning* that value. I'll
leave the querying part to you and just `return 50`.

[[[ code('05c61ac5b9') ]]]

But hold on a second... cause we just did something *totally* new! We're returning
a value from our handler! This is *not* something that we've done *anywhere* else.
Commands do work but *don't* return any value. A query doesn't really do any work,
its *only* point is to return a value.

Before we dispatch the query, open up `config/services.yaml` so we can
do our same trick of binding each handler to the correct bus. Copy the `Event\`
section, paste, change `Event` to `Query` in both places... then set the bus
to `query.bus`.

[[[ code('b31927d4b4') ]]]

Love it! Let's check our work by running:

```terminal
php bin/console debug:messenger
```

Yep! `query.bus` has one handler, `event.bus` has one handler and `command.bus` has
two.

## Dispatching the Message

Let's do this! Open up `src/Controller/MainController.php`. This renders the
homepage and so *this* is where we need to know how many photos have been uploaded.
To get the query bus, we need to know which type-hint & argument name combination
to use. We get that info from running:

```terminal
php bin/console debug:autowiring mess
```

We can get the main `command.bus` by using the `MessageBusInterface` type-hint
with *any* argument name. To get the query bus, we need to use that type-hint
*and* name the argument: `$queryBus`.

Do that: `MessageBusInterface $queryBus`. Inside the function, say
`$envelope = $queryBus->dispatch(new GetTotalImageCount())`.

[[[ code('6dc6de5e59') ]]]

## Fetching the Returned Value

We haven't used it too much, but the `dispatch()` method *returns* the final
Envelope object, which will have a number of different stamps on it. One of the
properties of a *query* bus is that every query will *always* be handled synchronously.
Why? Simple: we need the answer to our query... *right* now! And so, our handler
must be run *immediately*. In Messenger, there's nothing that *enforces* this on a
query bus... it's just that we won't ever route our queries to a transport, so
they'll always be handled right now.

Anyways, once a message is handled, Messenger automatically adds a stamp called
`HandledStamp`. Let's get that: `$handled = $envelope->last()` with
`HandledStamp::class`. I'll add some inline documentation above that to tell my
editor that this will be a `HandledStamp` instance.

[[[ code('dcca333caf') ]]]

So... why did we get this stamp? Well, we need to know what the *return* value of
our handler was. And, conveniently, Messenger stores that on this stamp! Get it
with `$imageCount = $handled->getResult()`.

[[[ code('e73f919b50') ]]]

Let's pass that into the template as an `imageCount` variable.... 

[[[ code('f7358992be') ]]]

and then in the template - `templates/main/homepage.html.twig` - because our 
entire frontend is built in Vue.js, let's override the `title` block on the 
page and use it there: `Ponka'd {{ imageCount }} Photos`.

[[[ code('ab2c848ae8') ]]]

Let's check it out! Move over, refresh and... it works! We've Ponka's 50 photos...
at least according to our hardcoded logic.

So... that's a query bus! It's not my favorite because we're not guaranteed
what *type* it returns - the `imageCount` could really be a string... or an object
of *any* class. Because we're not calling a *direct* method, the data we get
back feels a little fuzzy. Plus, because queries need to be handled
synchronously, you're not saving any performance by leveraging a query bus:
it's purely a programming pattern.

But, my opinion is *totally* subjective, and a lot of people love query buses. In
fact, we've been talking mostly about the *tools* themselves: command, event & query
buses. But there are some deeper patterns like CQRS or event sourcing that these
tools can unlock. This is not something we currently use here on SymfonyCasts...
but if you're interested, you can read more about this topic -
[Matthias Noback's blog](https://matthiasnoback.nl/) is my favorite source.

Oh, and before I forget, if you look back on the Symfony docs... back on the
main messenger page... all the way at the bottom... there's a spot here about
getting results from your handler. It shows some shortcuts that you can use to
more easily get the value from the bus.

Next, let's talk about message handler *subscribers*: an alternate way to
configure a message handler that has a few extra options.
