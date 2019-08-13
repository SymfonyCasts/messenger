# Events & Event Bus

Messenger is a "message bus". And it turns out that a "message" is a pretty generic
term in computer science. In fact, there are *three* types of messages you'll
commonly hear about.

## Messages: Commands, Events & Queries

The first type of message is a "command". And *that* is the type we've
been creating so far: we create message classes that *sound* like a command:
`AddPonkaToImage` or  `DeleteImagePost` and whose handlers *do* some action.
When you create message classes & handlers that look like this, you're using
Messenger as a "command bus". And one of the, sort of, "rules" of commands is that
each command should have exactly one handler. That's the "command" design pattern.

The *second* type of message is an "event". If you create an "event" class and
pass it to Messenger, then you're using Messenger as an "event" bus. The difference
between what a "command" class looks like and what an "event" class looks like is
subtle: it comes down to naming conventions and what you're ultimately trying to
accomplish. An event is dispatched *after* something happens and can have zero
to many handlers. Don't worry, we'll see what this looks like soon.

The third type of message is a "query" and we'll talk about those later. For now,
let's focus on understanding events and how they're different from commands...
because... it *can* be super confusing. And Messenger, being a generic
"message bus" works perfectly with either.

## Creating a Second Bus

Before we create our first event, I'll close a few things and then open
`config/packages/messenger.yaml`. If our app leverages both commands *and* events,
it's *totally* ok to use just *one* bus to handle all of that. But, in the interest
of making our life a bit more difficult and learning more, let's continue to use
our existing bus *only* as a command bus and create a *new* bus to only use with
events.

To do that, under the `buses:` key, add a new one called, how about, `event.bus`.
Set this to `~` which is null... just because we don't have any other configuration
that we need to put here yet. *This* will cause a *new* `MessageBus` service
to be added to the container.

[[[ code('b1461e7a94') ]]]

So far, whenever we needed the message bus - like in `ImagePostController` - we
autowired it by using the `MessageBusInterface` type-hint. The question *now* is:
how can we get access to the *new* message bus service?

Find your terminal and run:

```terminal
php bin/console debug:autowiring
```

... which... explodes! My bad:

> Invalid configuration for path `framework.messenger`: you must specify `default_bus`

Copy the name of the default bus. Once you define more than one bus, you need a
`default_bus` key set to your "main" bus. This tells Symfony which MessageBus service
to pass us when we use the `MessageBusInterface` type-hint.

[[[ code('173c8b2076') ]]]

Try the `debug:autowiring` command again... and search for "mess".

```terminal-silent
php bin/console debug:autowiring
```

Ah, *now* we have *two* entries! This tells me that if we use the
`MessageBusInterface` type-hint, we'll get the `messenger.bus.default` service.
Ignore the `debug.traced` part - that's just Symfony adding some debug tools.
But *now*, if you use the `MessageBusInterface` type-hint *and* you name the
argument `$eventBus`, it will pass you the new event bus service!

This is a new feature in Symfony 4.2 where you can autowire things by a combination
of the type-hint *and* argument name. Symfony took the name of our bus - `event.bus` -
and made it possible to use the `$eventBus` argument name.

## Differences Between Buses

Great! We now know how to get the event bus! But.. what's the difference between
these two buses? Do they behave differently? The answer is... no!

A bus is nothing more than a set of middleware. If you have two bus objects that
have the same middleware... well then... those message buses effectively *are*
identical! So, other than the fact that, so far, we've only added our `AuditMiddleware`
to the first bus, these buses will work and act identically. That's why, even
though I've created one service to handle commands and another service to handle
events... ah... we really could send all our commands and events to just *one*
service.

Next, let's create an event, learn what it looks like, why we might use them, and
how they're different than commands.
