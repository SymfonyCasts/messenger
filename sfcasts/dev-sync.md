# Handling Messages Sync while Developing

I *love* the ability to defer work for later by sending messages to a transport.
But, there *is* at least one practical bummer: it makes it a bit harder to actually
*develop* and code your app. In addition to setting up your web server, database
and anything else, you *now* need to remember to run:

```terminal
php bin/console messenger:consume
```

Otherwse... things won't *fully* work. If you have a robust setup for local
development - maybe something using Docker - you could build this right into that
setup so that it runs automatically. Except... you'd *still* need to remember to
*restart* the worker any time you make a change to some code that it uses.

It's not the *worst* thing ever. But, if this drives you crazy, there *is* a really
nice solution: tell Messenger to handle all of your messages synchronously when
you're in the `dev` environment.

## Hello "sync" Transport

Check out `config/packages/messenger.yaml`. One of the commented-out parts of this
file is a, kind of, "suggested" transport called `sync`. The really important part
isn't the name `sync` but the DSN: `sync://`. We learned earlier that Messenger
supports *several* different *types* of transport like Doctrine, redis and AMQP.
And the way you *choose* which one you want is the beginning of the connection
string, like `doctrine://`. The `sync` transport is really neat: instead of *truly*
sending each message to an external queue... it just handles them immediately.
They're handled synchronously.

## Making the Transports sync

We can take advantage of this and use a configuration trick to change our
`async` and `async_priority_high` transports to use the `sync://` transport *only*
when we're in the `dev` environment.

Go into the `config/packages/dev` directory. Any files here are *only* loaded in
the `dev` environment and *override* all values from the main `config/packages`
directory. Create a new file called `messenger.yaml`... though the name of this
file isn't important. Inside, we'll put the same configuration we have in our main file: `framework`, `messenger`, `transports`. Then override `async` and set
it to `sync://`. Do the same for `async_priority_high`: set it to `sync://`.

[[[ code('59134df695') ]]]

That's it! In the *dev* environment, *these* values will override the `dsn` values
from the main file. And, we can see this: in an open terminal tab, run:

```terminal
php bin/console debug:config framework messenger
```

This command shows you the real, *final* config under `framework` and `messenger`.
And... yea! Because we're currently in the `dev` environment, both transports
have a `dsn` set to `sync://`.

I *do* want to mention that the `queue_name` option is something that's specific
to Doctrine. The `sync` transport doesn't use that, and so, it ignores it. It's
possible that in a future version of Symfony, this would throw an error because
we're using an undefined option for this transport. If that happens, we would just
need to change the YAML format to set the `dsn` key - like we do in the main
`messenger.yaml` file - and then override the `options` key and set it to an
empty array. I'm mentioning that *just* in case.

Ok, let's try this! Refresh the page to be safe. Oh, and before we upload
something, go back to the terminal where our worker is running, hit Control+C to
stop it, and restart it. Woh! It's busted!

> You cannot receive messages from the sync transport.

Messenger is saying:

> Yo! Um... the SyncTransport isn't a *real* queue you can read from... so
> stop trying to do it!

It's right... and this is exactly what we wanted: we wanted to be able to have our
handlers called in the `dev` environment *without* needing to worry about running
this command.

Ok, *now* let's try it: upload a couple of photos and... yea... it's *super* slow
again. But Ponka *is* added when it finishes. The messages are being handled
synchronously.

To make sure this is *only* happening for the `dev` environment, open up the
`.env` file and change `APP_ENV` to be `prod` temporarily. Make sure to clear
your cache so this works:

```terminal
php bin/console cache:clear
```

Now, we *should* be able to run `messenger:consume` like before:

```terminal
php bin/console messenger:consume -vv async_priority_high async
```

And... we can! Sync messages in dev, async in prod.

Now that we've accomplished this, change `APP_ENV` back to `dev` and, just to
keep things more interesting for the tutorial, comment out the new `sync`
config we just added: I want to continue using our *real* transports while
we're coding. Stop and restart the worker:

[[[ code('08db7871ee') ]]]

Now that we're back in the `dev` environment, stop and restart the worker:

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

Next: let's talk about a similar problem: how do you handle transports when writing
automated tests?
