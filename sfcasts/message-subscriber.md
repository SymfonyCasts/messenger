# Advanced Handler Config: Handler Subscribers

Open up `DeleteImagePostHandler`. The *main* thing that a message bus needs to know
is the *link* between the `DeleteImagePost` message class and it handler: that
when we dispatch a `DeleteImagePost` object, it should call `DeleteImagePostHandler`.

How does Messenger know these two classes are connected? It knows because our
handler implements `MessageHandlerInterface` - which marks it as a message handler
*and* because its `__invoke()` method is *type-hinted* with `DeleteImagePost`.
If you follow these rules: implement that interface, create a method called
`__invoke()` and type-hint the first and only argument with the message class,
then... you're done!

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
> message class and `DeleteImagePostHandler`... but I *only* want you to configure
> the "command bus" about that connection... because that's the only bus I
> intend to dispatch that class to.

We also see that on `debug:messenger`: the command bus is aware of the
`DeleteImagePost` and `DeleteImagePostHandler` connection and the other two buses
know about *other* message and message handler links. Oh, and as a reminder, if
this whole "tags" thing confuses you... skip it - it organizes things a bit more,
but you can just as effectively have *one* bus that handles everything.

Anyways, this system is quick to use but there are a *few* things that you *can't*
change. For example, the method in your handler *must* be called `__invoke()`...
that's just what Symfony looks for. And because a class can only have one method
named `__invoke()`, this means that you can't have a single handler class that
handles multiple different message classes. I don't usually like to have one
handler handle different messages anyways... but it *is* technically a limitation.

## MessageHandlerInterface

Now that we're reviewed *all* of that... it turns out that we can take *more*
control of how a message class is linked to a handler... and include even more
config related to that.

How? Instead of implementing `MessageHandlerInterface`, implement
`MessageSubscriberInterface`.

This is less of a huge change than it may see. If you open up
`MessageSubscriberInterface`,
it *extends* `MessageHandlerInterface`. So, we're still *effectively* implementing
same interface... but now we're forced to have one new method:
`getHandledMessages()`.

At the bottom of my class, I'll go to Code -> Generate - or Command + N on a Mac -
and select ""Implement Methods"".

As soon as we implement this interface, instead of magically looking for the
`__invoke()` method and checking the type-hint on the argument for which message
class this handler handles, Symfony will call this method. Our job here? Tell
it *exactly* which classes we handle, which method to call and... some other fun
stuff!

## Message Handling Config

The easiest thing you can put here is `yield DeleteImagePost::class`. Don't
over-think that yield... it's just syntax sugar. You could also return an array
with a `DeleteImagePost::class` string inside.

What difference did that make? Go back and run `debug:messenger`.

```terminal-silent
php bin/console debug:messenger
```

And... it made absolutely *no* difference. With this *super* simple config, we've
told Messenger that this class handles `DeleteImagePost` objects... and then
Messenger *still* assumes that it should execute a method called `__invoke()`.

But technically, this type-hint isn't needed anymore. Delete that, then re-run:

```terminal-silent
php bin/console debug:messenger
```

It *still* sees the connection between the message class and handler.

## Controlling the Method & Handling Multiple Classes

Ok... but since we probably *should* use type-hints... this isn't that interesting
yet. What else can we do?

Well, by assigning this to an array, we can add some config. For example, we can
say `'method' => '__invoke'`. Yep, we can now *control* which method Messenger
will call. That's especially useful if you decide that you want to add *another*
yield to handle a *second* message and want Messenger to call a *different* method.

## Handler Priority

What else can we put here? One option is `priority` - let's set it to... 10.
This option is... much less interesting than it might look like at first.
We talked earlier about priority transports: in `config/packages/messenger.yaml`
we created *two* transport - `async` & `async_priority_high` - and we route
some messages to each. We did that so that, when we run our worker, we can tell
it to always read messages from `async_priority_high` first before reading messages
from `async`.

The `priority` option is... less powerful. If you send two messages to the
*same* transport with *different* priorities... they will *still* be read and
processed in the order they were sent. The `priority` does *not* influence how
"soon" they are processed. So... what does it do? Well, if `DeleteImagePost`
had *two* different handlers... and one had the default priority of zero and
another had 10, the handler with priority 10 would be called first.