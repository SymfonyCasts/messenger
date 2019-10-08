# Advanced Handler Config: Handler Subscribers

Open up `DeleteImagePostHandler`. The *main* thing that a message bus needs to know
is the *link* between the `DeleteImagePost` message class and its handler. It
needs to know that when we dispatch a `DeleteImagePost` object, it should call
`DeleteImagePostHandler`.

How does Messenger know these two classes are connected? It knows because our
handler implements `MessageHandlerInterface` - this "marks" it as a message handler -
*and* because its `__invoke()` method is *type-hinted* with `DeleteImagePost`.
If you follow these two rules - implement that interface & create an `__invoke()`
method with an argument type-hinted with the message class - then... you're done!

Find your terminal and run:

```terminal
php bin/console debug:messenger
```

Yep! This proves it: `DeleteImagePost` is handled by `DeleteImagePostHandler`.

Then... in `config/services.yaml`, we got a little bit fancier. By organizing
each *type* of message - commands, events and queries - into different directories,
we were able to add a *tag* to each service. This gives a bit *more* info to
Messenger. It says:

> Hey! I want you to make that normal connection between the `DeleteImagePost`
> message class and `DeleteImagePostHandler`... but I *only* want you to tell
> the "command bus" about that connection... because that's the only bus I'm
> going to dispatch that message into.

We also see this on `debug:messenger`: the command bus is aware of the
`DeleteImagePost` and `DeleteImagePostHandler` connection and the other two buses
know about *other* message and message handler links. Oh, and as a reminder, if
this whole "tags" thing confuses you... skip it. It organizes things a bit more,
but you can just as effectively have *one* bus that handles everything.

Anyways, this system is quick to use but there are a *few* things that you *can't*
change. For example, the method in your handler *must* be called `__invoke()`...
that's just what Symfony looks for. And because a class can only have one method
named `__invoke()`, this means that you can't have a single handler that handles
multiple different message classes. I don't *usually* like to do this anyways,
I prefer one message class per handler... but it *is* a technical limitation.

## MessageHandlerInterface

Now that we've reviewed *all* of that... it turns out that this is only *part*
of the story. If we want to, we can take *more* control of how a message class
is linked to its handler... including some extra config.

How? Instead of implementing `MessageHandlerInterface`, implement
`MessageSubscriberInterface`.

[[[ code('09d6bf3c0e') ]]]

This is less of a huge change than it may seem. If you open up
`MessageSubscriberInterface`, it *extends* `MessageHandlerInterface`. So, we're
still *effectively* implementing the same interface... but now we're forced to
have one new method: `getHandledMessages()`.

At the bottom of my class, I'll go to Code -> Generate - or Command + N on a Mac -
and select "Implement Methods".

As soon as we implement this interface, instead of magically looking for the
`__invoke()` method and checking the type-hint on the argument for which message
class this should handle, Symfony will call this method. Our job here? Tell
it *exactly* which classes we handle, which method to call and... some *other* fun
stuff!

[[[ code('9dc404486a') ]]]

## Message Handling Config

The easiest thing you can put here is `yield DeleteImagePost::class`. Don't
over-think that yield... it's just syntax sugar. You could also return an array
with a `DeleteImagePost::class` string inside.

[[[ code('0ac50455c8') ]]]

What difference did that make? Go back and run `debug:messenger`.

```terminal-silent
php bin/console debug:messenger
```

And... it made absolutely *no* difference. With this *super* simple config, we've
told Messenger that this class handles `DeleteImagePost` objects... and then
Messenger *still* assumes that it should execute a method called `__invoke()`.

But technically, this type-hint isn't needed anymore. Delete that, then re-run:

```terminal
php bin/console debug:messenger
```

It *still* sees the connection between the message class and handler.

## Controlling the Method & Handling Multiple Classes

Ok... but since we probably *should* use type-hints... this isn't that interesting
yet. What else can we do?

Well, by assigning this to an array, we can add some config. For example, we can
say `'method' => '__invoke'`. Yep, we can now *control* which method Messenger
will call. That's especially useful if you decide that you want to add *another*
yield to handle a *second* message... and want Messenger to call a *different* method.

[[[ code('8fdab60f6b') ]]]

## Handler Priority

What else can we put here? One option is `priority` - let's set it to... 10.

[[[ code('1d6175bba9') ]]]

This option is... much less interesting than it might look like at first.
We talked earlier about priority transports: in `config/packages/messenger.yaml`
we created *two* transport - `async` & `async_priority_high` - and we route
different messages to each. We did this so that, when we run our worker, we can
tell it to always read messages from `async_priority_high` first before reading
messages from `async`. That makes `async_priority_high` a place for us to send
"higher" priority messages.

The `priority` option here is... less powerful. If you send a message to a transport
with a priority 0 and then you send *another* message to that *same* transport with
priority 10, what do you think will happen? Which message will be handled first?

The answer: the first message that was sent - the one with the *lower* priority.
Basically, Messenger will *always* read messages in a first-in-first-out basis:
it will *always* read the *oldest* messages first. The `priority` does *not*
influence this.

So... what does it do? Well, if `DeleteImagePost` had *two* handlers... and one
had the default priority of zero and another had 10, the handler with priority
10 would be called first. That's not *usually* important, but could be if you
had two event handlers and *really* needed them to happen in a certain order.

Next, let's talk about *one* more option you can pass here - the most *powerful*
option. It's called `from_transport` and allows you to, sort of, send different
"handlers" of a message to different transports so that each can be consumed
independently.
