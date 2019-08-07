# Doctrine Transaction & Validation Middleware

We're now using both a command bus pattern, where we create commands and command
handlers, and the event bus pattern: we have our first event and event handler.
The difference between a command and event... is a little subtle. Each command
should have exactly one handler: we're *commanding* that something perform a
specific action: `AddPonkaToImage`. But an event is something that's usually
dispatched *after* that action is taken, and the purpose is to allow anyone
else to take any *secondary* action - to *react* to the action.

## Two Buses... Why?

Obviously, Messenger itself is a generic enough tool that it can be used for both
of these use cases. Open up `config/packages/messenger.yaml`. We decided to
register one bus service that we're using as our *command* bus and a *separate*
bus service that we're using as our event bus. But... there's really almost
*no* difference between these two buses! A bus is nothing more than a collection
of middleware... so the *only* differences are that the first has
`AuditMiddleware`... which we could also add to the second... and we told
the `HandleMessageMiddleware` on the event bus to allow "no handlers" for a message:
if an event has *zero* handlers, it won't throw an exception.

But really... this is *so* minor that if you wanted to use just *one* bus for
everything, that would work great.

## Validation, Doctrine Transaction, etc Middleware

However, there *are* some people that make their command and event buses a bit
*more* different. Google for "Symfony Messenger multiple buses" to find an article
that talks about how to manage multiple buses. In this example, the docs show
*three* different buses: the command bus, a query bus - which we'll talk about in
a minute - and an event bus. But each bus has *slightly* different middleware.

These two middleware - `validation` and `doctrine_transaction` - come automatically
with Symfony but aren't enabled by default. If you add the `validation` middleware,
when you dispatch a message, that middleware will *validate* the message object
*itself* through Symfony's validator. If validation fails, it will throw a
`ValidationFailedException` that you can catch in your code to read off the
errors.

This is cool... but we're *not* using this because I prefer to validate my data
*before* sending it into the bus. It just makes more sense to me and looks a bit
simpler than a, somewhat, "invisible" layer doing validation for us. But, it's a
totally valid thing to use.

The `doctrine_transaction` middleware is similar. If you activate this middleware,
it will wrap your handler inside a Doctrine transaction. If the handler throws
an exception, it will rollback the transaction. And if *no* exception is thrown,
it will commit it. This means that your handler won't need to call `flush()` on
the EntityManager: the middleware does that for you.

This is cool... but I'm ok with creating and managing Doctrine transactions
myself if I need them. So, this is another nice middleware that I *like*, but
don't use.

Anyways, if you *do* use more middleware than we're using, then your different
buses *might* start to... actually be *more* different... and using multiple bus
services would make more sense. Like with everything, if the simpler approach -
using one bus for everything - is working for you, great! Do that. If you need
flexibility to have different middleware on different buses, awesome. Configure
multiple buses.

Since *multiple* buses is the more complex use-case... and we're deep-diving into
Messenger, let's keep our multiple bus setup and get our code organized even better
around this concept.

## Messages Sent to Wrong Bus

Find your terminal and run:

```terminal
php bin/console debug:messenger
```

Ah... Now that we have multiple buses, it breaks down the information on a
bus-by-bus basis. It says that the following messages can be dispatched to our
command bus and... huh... these *same* messages are allowed to be dispatched to
the event bus.

That's... ok... but it's not *really* want we want. *We* know that certain messages
are *commands* and will be sent to the command bus and others are events. But when
we set up our handlers, we never told Messenger that *this* handler should only
be used by *this* bus. So, Messenger makes sure that *all* buses are aware of
*all* handlers. That's not a huge deal, but it means that if we accidentally
took this command and dispatched it to the event bus, it would work! And if
we took this event and sent it to the command bus, *it* would work. If we're relying
on each bus to have quite different middleware, we probably *don't* want to make
that mistake.

So... we're going to do something *totally* optional... but nice, when you're
using events and commands. Look inside the `Message` and `MessageHandler` directories:
we have a mixture of events and commands. Sure, I put the event into an `Event/`
subdirectory, but we haven't done the same for commands.

Let's do that next: let's organize our message & message handlers better. Once
we do this, we can use a service configuration trick to make sure that the command
bus *only* knows about the command handlers and the event bus *only* knows about
the event handlers.
