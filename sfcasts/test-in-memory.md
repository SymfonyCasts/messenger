# Testing with the "in-memory" Transport

A few minutes ago, in the `dev` environment only, we overrode all our transports
so that all messages were handled synchronously. We commented it out for now, but
this is *also* something that you could choose to do in your `test` environment,
so that when you run the tests, the messages are handled *within* the test.

This may or may not be what you want. On one hand, it means your functional test
is testing more. On the other hand, a functional test should probably test that
the endpoint works and the message is sent to the transport, but testing the
handler itself should be done in a test specifically for that class.

That's what we're going to do now: figure out a way to *not* run the handlers
synchronously but *test* that the message *was* sent to the transport. Sure, if
we killed the worker, we could query the `messenger_messages` table, but that's
a bit hacky - and only works if you're using the Doctrine transport. Fortunately,
there's a more interesting option.

Start by copying `config/packages/dev/messenger.yaml` and pasting that into
`config/packages/test/`. This gives us messenger configuration that will *only*
be used in the `test` environment. Uncomment the code, and replace `sync` with
`in-memory`. Do that for both of the transports.

[[[ code('83a3e94953') ]]]

The `in-memory` transport is really cool. In fact, let's look at it! I'll hit
`Shift+Shift` in PhpStorm and search for `InMemoryTransport` to find it.

This... is basically a fake transport. When a message is sent to it, it doesn't
handle it or send it anywhere, it stores it in a property. If you were to use this
in a real project, the messages would then disappear at the end of the request.

But, this is *super* useful for testing. Let's try it. A second ago, each time
we ran our test, our worker *actually* started processing those messages... which
makes sense: we really *were* delivering them to the transport. Now, I'll clear
the screen and then run:

```terminal
php bin/phpunit
```

It still works... but *now* the worker does nothing: the message isn't *really*
being sent to the transport anymore and it's lost at the end of our tests. But!
From within the test, we can now *fetch* that transport and *ask* it how many
messages were sent to it!

## Fetching the Transport Service

Behind the scenes, every transport is actually a service in the container. Find your
open terminal and run:

```terminal
php bin/console debug:container async
```

There they are: `messenger.transport.async` and
`messenger.transport.async_priority_high`. Copy the second service id.

We want to verify that the `AddPonkaToImage` message is sent to the transport,
and we know that it's being routed to `async_priority_high`.

Back in the test, this is super cool: we can fetch the *exact* transport object
that was just used from within the test by saying:
`$transport = self::$container->get()` and then pasting the service id:
`messenger.transport.async_priority_high`

[[[ code('b00f9a8299') ]]]

This `self::$container` property holds the container that was actually used
during the test request and is designed so that we can fetch *anything* we want
out of it.

Let's see what this looks like: `dd($transport)`.

[[[ code('a304563f40') ]]]

Now jump back over to your terminal and run:

```terminal
php bin/phpunit
```

Nice! This dumps the `InMemoryTransport` object and... the `sent` property *indeed*
holds our *one* message object! All we need to do now is add an assertion for this.

Back in the test, I'm going to help out my editor by adding some inline docs to
advertise that this is an `InMemoryTransport`. Below add `$this->assertCount()` to
assert that we expect one message to be returned when we say `$transport->`...
let's see... the method that you can call on a transport to get the sent, or "queued"
messages is `get()`.

[[[ code('b5621e1b35') ]]]

Let's try it! Run:

```terminal
php bin/phpunit
```

Got it! We're now guaranteeing that the message was sent but we've kept our tests
faster and more directed by not trying to handle them synchronously. If we were
using something like RabbitMQ, we also don't need to have that running whenever
we execute our tests.

Next, let's talk deployment! How do we run our workers on production... and make
sure they stay running?
