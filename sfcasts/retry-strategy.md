# Retry Delay & Retry Strategy

By default, a message will be retried three times then lost forever. Well...
in a few minutes... I'll show you how you can *avoid* even *those* messages from
being lost.

Anyways... the process... just works! And it's even cooler than it looks at first.
It's a bit hard to see - especially because there's a sleep in our handler - but
this message was sent for retry #3 at the 13 second timestamp and it was finally
handled again down at the 17 second timestamp - a 4 second delay. That delay was
*not* caused by our worker just being busy until then: it was 100% intentional.

Check it out: I'll hit Ctrl+C to stop the worker and then run:

```terminal
php bin/console config:dump framework messenger
```

This should give us a big tree of "example" configuration that you can put under
the `framework` `messenger` config key. I *love* this command: it's a *great* way
to find options that you maybe didn't know existed.

Cool! Look closely at the `transports` key - it lists an "example" transport below
with *all* the possible config options. One of them is `retry_strategy` where we
can control the maximum number of retries and the *delay* that should happen between
those retries.

This `delay` number is smarter than it looks: it works together with the "multiplier"
to create an exponentially growing delay. With these settings, the first retry will
delay one second, the second 2 seconds and the third *4* seconds.

This is important because, if a message fails due to some temporary issue - like
connecting to a third-party server - you might *not* want to try again immediately.
In fact, you might choose to set these to *way* higher values so that it retries
maybe 1 minute or even a day later.

Let's also try a similar command:

```terminal
php bin/console debug:config framework messenger
```

Instead of showing *example* config, this tells us what our *current* configuration
is, including any default values: our `async` transport has a `retry_strategy`,
which is defaulting to 3 max retries with a 1000 millisecond delay and a multiplier
of 2.

## Configuring the Delay

Let's make this a bit *more* interesting. In the handler, let's make it *always*
fail by adding `|| true`.

[[[ code('5abb95e95c') ]]]

Now, under `messenger`, let's play with the retry config. Wait... but the `async`
transport is set to a string... are we allowed to include config options under
that? No! Well, yes, sort of. As soon as you need to configure a transport beyond
just the connection details, you'll need to drop this string onto the next line
and assign it to a `dsn` key. *Now* we can add `retry_strategy`, and let's set
the delay to 2 seconds instead of 1.

[[[ code('4f54fafce9') ]]]

Oh, and I also want to mention this `service` key. If you want to *completely*
control the retry config - maybe even having different retry logic per message -
you can create a service that implements `RetryStrategyInterface` and put its
service id - usually its class name - right here.

Anyways, let's see what happens with the longer delay: restart the worker process:

```terminal-silent
php bin/console messenger:consume -vv
```

This time, upload just *one* photo so we can watch it fail over and over again.
And... yep! It fails and sends for retry #1... then fails again and sends for
retry #2. But check out that delay! 09 to 11 - 2 seconds - then 11 to 15 - a
4 second delay. And... if... we... are... super... patient... yea! Retry #3
starts a full *8* seconds later. Then it's "rejected" - removed from the queue -
and lost forever. Tragic!

Retries are great... but I don't like that last part: when the message is eventually
lost forever. Change the delay to 500 - it'll make this easier to test. 

[[[ code('96e3408eba') ]]]

Next, let's talk about a special concept called the "failure transport": 
a better alternative than allowing failed messages to simply... disappear.
