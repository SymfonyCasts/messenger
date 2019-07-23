# Middleware

Internally, when you dispatch a message onto the bus... what happens? What does
the code look like inside the bus? The answer is... there basically *is* no code
inside the bus! *Everything* is done via middleware.

## Middleware Basics

The bus is nothing more than a collection of "middleware". And each middleware is
just a function that receives the message and can do something with it.

The process looks like this. We pass a message to the `dispatch()` method, then
the bus passes *that* to the first middleware. The middleware then runs some code
and eventually calls the *second* middleware. It runs some code and eventually
calls the *third* middleware... until finally the last middleware - let's say
it's the fourth middleware - has no one else to call. At that moment, the fourth
middleware function finishes, then the third middleware function finishes, then
the second, then the first. Thanks to this design, each middleware can run code
*before* calling the next middleware or *after*.

This "middleware" concept isn't unique to Messenger or even PHP - it's a pattern.
It can be both super useful... and a bit confusing... as it's a big circle. The
point is this: with Messenger, if you want to hook into the dispatch process - like
to log what's happening - you'll do that with a middleware. Heck, even the core
functionality of messenger - executing handlers and sending messages to transports -
is done with middleware! Those are called `HandleMessageMiddleware` and
`SendMessageMiddleware` if you want to geek out and see how they work.

So here's our goal: each time we dispatch a message... from *anywhere*, I want to
attach a unique id to that message and then use that to log what's happening
over time to the message: when it's initially dispatched, when it's sent to the
transport, and when it's *received* from the transport and handled. Heck, you could
even use this to track how *long* an individual message took before it was
processed or how many times it was retried.

## Creating a Middleware

*Creating* a middleware is actually fairly simple. Create a new directory
inside `src/` called `Messenger/`... though... like with pretty much *everything*
in Symfony, this directory could be called anything. Inside, add a class called,
how about, `AuditMiddleware`.

[[[ code('9d6147439d') ]]]

The only rule for middleware is that they must implement - surprise! -
`MiddlewareInterface`. I'll go to "Code -> Generate" - or Command+N on a Mac - and
select "Implement Methods". This interface requires just one: `handle()`. We'll
talk about the "stack" thing in a second... but mostly... the signature of this
method makes sense: we receive the `Envelope` and *return* an `Envelope`.

[[[ code('a19817ab3f') ]]]

The one line that your middleware will almost definitely need is this:
`return $stack->next()->handle($envelope, $stack)`.

[[[ code('1cdc3fbcef') ]]]

*This* is the line that basically says:

> I want to execute the next middleware and then return its value.

Without this line, any middleware after us would *never* be called... which isn't
*usually* what you want.

## Registering the Middleware

And... to start... that's enough: this class is already a functional middleware!
But, unlike a lot of stuff in Symfony, Messenger won't find and start using this
middleware automatically. Find your open terminal and, once again, run:

```terminal
php bin/console debug:config framework messenger
```

Let's see... somewhere in here is a key called `buses`. This defines all of the
message bus services you have in your system. Right now, we have one: the
default bus called `messenger.bus.default`. That name could be anything and
becomes the service id. Below this, we can use the `middleware` key to define
whatever *new* middleware we want to add, in addition to the core ones that are
added by default.

Let's copy that config. Then, open `config/packages/messenger.yaml` and, under
`framework:`, `messenger:`, paste this right on top... and make sure it's indented
correctly. Below, add `middleware:` a new line, then our new middleware service:
`App\Messenger\AuditMiddleware`.

[[[ code('4fe287ea10') ]]]

## Order of Middleware

And just like that, our middleware *should* be called... along with all the *core*
middleware. What... um... *are* the core middleware? And what order is everything
called in? Well, there's not a great way to see that yet, but you *can* find this
information by running:

```terminal
php bin/console debug:container --show-arguments messenger.bus.default.inner
```

... which is a *super* low-level way to get information about the message bus.
Anyways, there are a few core middleware at the start that get some basic things
set up, then *our* middleware, and finally, `SendMessageMiddleware` and
`HandleMessageMiddleware` are called at the end. Knowing the exact order of this
stuff isn't that important - but hopefully it'll help demystify things as we
keep going.

Next, let's get to work by using our middleware to attach a unique id to each
message. How? Via our very own stamp!
