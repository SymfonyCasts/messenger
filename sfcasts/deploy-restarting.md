# Killing Workers Early & on Deploy

Run:

```terminal
php bin/console messenger:consume --help
```

We saw earlier that this has an option called `--time-limit`, which you can use
to tell the command to run for 60 minutes and then exit. The command *also* has
two other options - `--memory-limit` - to tell the command to exit once its memory
usage is above a certain level - or `--limit` - to tell it to run a specific *number*
of messages and then exit. All of these are *great* options to use because we really
*don't* want our `messenger:consume` command to run too long: we really just want
it to handle a few messages, then exit. Restarting the worker is handled by Supervisor
and doesn't take a huge amount of resources. All of these options cause the worker
to exit *gracefully*, meaning, it only exits *after* a message has been fully handled,
never in the *middle* of it. But, if you let your worker run too long and it runs
out of memory... that *would* cause it to exit in the middle of handling a message
and... well... that's not great. Use these options. You can even use *all* of them
at once.

## Restarting Workers on Deploy

There's also a completely different situation when you want *all* of your workers
to restart: whenever you deploy. We've seen *why* many times already: whenever we
make a change to our code, we've been manually restarting the `messenger:consume`
command so that the worker *sees* the new code. The same thing will happen
on production: when you deploy, your workers *won't* see the new code until they
exit and are restarted. Right now, that could take up to *six* minutes to happen!
That is not okay. Nope, at the moment we deploy, we need all of or worker processes
to exit, and we need that to happen gracefully.

Fortunately, Symfony has our back. Once again, run `ps -A` to see the worker processes.

```terminal-silent
ps -A | grep messenger:consume
```

Now, pretend we've just deployed. To stop all the workers, run:

```terminal
php bin/console messenger:stop-workers
```

Check the processes again:

```terminal-silent
ps -A | grep messenger:consume
```

Ha! Perfect! The two new process ids *prove* that the workers were restarted!
How does this work? Magic! I mean, *caching*. Seriously.

Behind the scenes, this command sends a signal to each worker that it should exit.
But the workers are smart: they don't exit *immediately*, they finish whatever
message they're handling and *then* exit: a graceful exit. To send this signal,
Symfony actually sets a flag in the cache system - and each worker checks this
flag. If you have a multi-server setup, you'll need to make sure that your
Symfony "app cache" is stored in something like Redis or Memcache instead of the
filesystem so that everyone can read those keys.

## What Happens when you Deploy Message Class Changes

There's *one* more detail you need to think about and it's due to the asynchronous
nature of handling messages. Open up `AddPonkaToImage`. Imagine that
our site is currently deployed and the `AddPonkaToImage` class looks like this.
When someone uploads an image, we serialize this class and send it to the transport.

Imagine now that we have a bunch of these messages sitting in the queue at the moment
we deploy a new version of our site. In this new version, we've refactored
the `AddPonkaToImage` class: we've renamed `$imagePostId` to `$imagePost`. What
will happen when those *old* versions of `AddPonkaToImage` are loaded from the
queue?

The answer... the new `$imagePost` property will be null... and some non-existent
`$imagePostId` property would be set instead. And that would probably cause your
handler some serious trouble. So, *if* you need to tweak some properties on an
existing message class, you have two options. First, don't: create a *new* message
class instead. Then, *after* you deploy, remove the old message class. *Or* second,
update the message class but, temporarily, keep both the old and new properties and
make your handler smart enough to look for both. Again, after one deploy, or really,
once you're sure all the old messages have been processed, you can remove the old
stuff.

And... that's it! Use Supervisor to keep your processes running and the
`messenger:stop-workers` command to restart on deploy. You are ready to put this
stuff into production.

Before we keep going, I'm going to find my terminal and run:

```terminal
supervisorctl -c /usr/local/etc/supervisord.ini stop messenger-consume:*
```

That stops the two processes. Now I'll run my worker manually:

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

This just makes life easier and more obvious locally: I can see the output from
my worker.

Next: we've talked about commands & command handlers. Now it's time to talk about
*events* and *event handlers*, how we can use Messenger as an event bus and...
what the heck that means.
