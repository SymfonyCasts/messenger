# Investigating & Retrying Failed Messages

Apparently now that we've configured a `failure_transport`, if handling a message
*still* isn't working after 3 retries, instead of being sent to `/dev/null`, they're
sent to another transport - in our case called "failed". That transport is... really...
the same as *any* other transport... and we *could* use the `messenger:consume` command
to try to process those messages again.

But, there's a better way. Run:

```terminal
php bin/console messenger
```

## Seeing Messages on the Failed Queue

Hey! Shiny new commands are hiding here! *Three* under `messenger:failed`.
Try out that `messenger:failed:show` one:

```terminal-silent
php bin/console messenger:failed:show
```

Nice! *There* are our *4* failed messages... just sitting there wait for us to look
at them. Let's pretend that we're not sure what went wrong with these messages and
want to check them out. Start by passing the 115 id:

```terminal-silent
php bin/console messenger:failed:show 115
```

I love this: it shows us the error message, error class and a history of the
message's misadventures through our system! It failed, was redelivered to the async
transport at 05, at 06 and then at 07, it finally failed and was redelivered to
the `failed` transport.

If we add a `-vv` on the command...

```terminal-silent
php bin/console messenger:failed:show 115 -vv
```

*Now* we can see a full stack trace of what happened on that exception.

This is a really powerful way to figure out what went wrong and what to do next:
do we have a bug in our app that we need to fix before retrying this? Or maybe it
was a temporary failure and we can try again now? Or maybe, for some reason, we
just want to remove this message entirely.

If you *did* want to remove this without retrying, that's the
`messenger:failed:remove` command.

## Retrying Failed Messages

But... let's retry this! Back in the handler, change this back to fail randomly.

[[[ code('b1bf4ef9ef') ]]]

There are two ways to work with the retry command: you can retry a specific id
like you see here *or* you can retry the messages one-by-one. Let's do that. Run:

```terminal
php bin/console messenger:failed:retry
```

This is kind of similar to how `messenger:consume` works, except that it asks
you before trying each message and, instead of running this command *all* the time
on production, you'll run it manually whenever you have some failed messages
that you need to process.

Cool! We see the details and it asks if we want to retry this. Like with show,
you can pass `-vv` to see the *full* message details. Say "yes". It processes...
and then continues to the next. Actually, let me try that again with `-vv` so
we can see what's going on:

```terminal-silent
php bin/console messenger:failed:retry -vv
```

## When Failed Messages... Fail Again

*This* time we see *all* the details. Say "yes" again and... nice: "Received message",
"Message handled" and onto the *next* message. We're on a roll! Notice that this
message's id is 117 - that'll be important in a second. Hit yes to retry this
message too.

Woh! This time it failed again! What does that mean? Well remember, the failure
transport is *really* just a normal transport that we're using in a special way.
And so, when a message fails here, Messenger... retries it! Yea it was sent *back*
to the failure transport!

I'll hit Control+C and re-run the show command:

```terminal-silent
php bin/console messenger:failed:show
```

That id 119 was *not* there when we started. Nope, when message 117 was processed,
it failed, was *redelivered* to the failure transport as id 119, and *then* was
removed. And so, unless you change your configuration, messages will be retried 3
times on the failure transport before *finally* being *completely* discarded.

Oh, but if you look at the retried message closer:

```terminal-silent
php bin/console messenger:failed:show 119 -vv
```

There's a bit of a bug: the error and error class are missing. The data *is* still
in the database... it's just not displayed correctly here. But you *can* see the
message's history: including that it was sent to the `failed` transport and then
sent *again* to the `failed` transport.

By the way, you can pass a `--force` option to the `retry` command if you
want it to retry messages one-by-one *without* asking you each time whether or
not it should do it. Also, not *all* the transport types - like AMQP or Redis -
support *all* of the features we just saw if you use it as your failure transport.
That may change in the future, but at this moment - Doctrine is the *most* robust
transport to use for failures.

Anyways, as cool as failing is, let's go back and remove the code that's breaking
our handler. Because... it's time to take a step deeper into how Messenger works:
it's time to talk about middleware.
