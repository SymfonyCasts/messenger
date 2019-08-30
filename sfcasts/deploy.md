# Deployment & Supervisor

So... how does all of this work on production? It's a simple problem really: on
production, we somehow need to make sure that this command - `messenger:consume` -
is *always* running. Like, *always*.

Some hosting platforms - like SymfonyCloud - allow you to do this with some
simple configuration. You basically say:

> Yo Cloud provider thingy! Please make sure that `bin/console messenger:consume`
> is *always* running. If it quits for some reason, start a new one.

If you're *not* using a hosting platform like that, it's ok - but you *will* need
to do a little bit of work to get that same result. And actually, it's not *just*
that we need a way to make sure that someone starts this command and then it
runs forever. We actually *don't* want the command to run forever. No matter how
well you write your PHP code, PHP just isn't meant to be ran *forever* - eventually
your memory footprint will increase too much and the process will die. And... that's
perfect! We *don't* want our process to run forever. Nope: what we *really* want
is for `messenger:consume` to run, handle... a few messages... then close itself.
Then, we'll use a *different* tool to make sure that each time the process
disappears, it gets restarted.

## Hello Supervisor

The tool that does that is called supervisor. After you install it, you give it
a command that you *always* want running and it stays up *all* night *constantly*
eating pizza and watching to make sure that command is running. The *moment* it
stops running, for *any* reason, it puts down the pizza and it restarts the command.

So let's see how Supervisor works and how we can use it to make sure our worker
is *always* running. Because I'm using a Mac, I already installed Supervisor
via Brew. If you're using Ubuntu, you can install it via apt. By the way, you
don't *actually* need to install & configure Supervisor on your local machine:
you only need it on production. We're installing it so we can test and make sure
everything works.

## Supervisor Configuration

To get it going, we need a supervisor configuration file. Google for
"Messenger Symfony" and open the main documentation. In the middle... there's a
spot that talks about supervisor. Copy the configuration file. We could put
this anywhere: it doesn't need to live in our project. But, I like to keep it in
my repo so I can store it in git. In... how about `config/`, create a new file
called `messenger-worker.ini` and paste the code inside.

[[[ code('ade99551b3') ]]]

The file tells Supervisor which command to run and other important info like which
user it should run the process as and the *number* of processes to run. This will
create *two* worker processes. The more workers you run, the more messages can
be handled at once. But also, the more memory & CPU you'll need.

Now, locally, I don't need to run supervisor... because we can just manually run
`messenger:consume`. But to make sure this all works, we're going to *pretend*
like my computer is production and change the path to point to use my local path:
`/Users/weaverryan/messenger`... which if I double-check in my terminal... oop - I
forgot the `Sites/` part. Then, down here, I'll change the user to be `weaverryan`.
Again, you would *normally* set this to your *production* values.

Oh, and if you look closely at the command, it's running
`messenger:consume async`. Make sure to also consume `async_priority_high`.
The command *also* has a `--time-limit=3600` option. We'll talk more about this
and some other options in a bit, but this is great: it tells the worker to run
for 60 minutes and then exit, to make sure it doesn't get too old and take up
too much memory. As soon as it exits, Supervisor will restart it.

## Running Supervisor

Now that we have our config file, we need to make sure Supervisor can see it.
Each Supervisor install has a *main* configuration file. On a Mac where it's
installed via Brew, that file is located at `/usr/local/etc/supervisord.ini`.
On Ubuntu, it should be `/etc/supervisor/supervisord.conf`.

Then, *somewhere* in your config file, you'll find an `include` section with a
`files` line. *This* means that Supervisor is looking in this directory to find
configuration files - like *ours* - that will tell it what to do.

To get *our* configuration file into that directory, we can create a symlink:
`ln -s ~/Sites/messenger/config/messenger-worker.ini` then paste the directory.

```terminal-silent
ln -s ~/Sites/messenger/config/messenger-worker.ini /usr/local/etc/supervisor.d/
```

Ok! Supervisor *should* now be able to see our config file. To *run* supervisor,
we'll use something called `supervisorctl`. Because I'm on a Mac, I *also* need to
pass a `-c` option and point to the configuration file we were just looking at.
If you're on Ubuntu, you shouldn't need to do this - it'll know where to look
already. Then say `reread`: that tells Supervisor to reread the config files:

```terminal-silent
supervisorctl -c /usr/local/etc/supervisord.ini reread
```

By the way, you *may* need to run this command with `sudo`. If you do, no big deal:
it will execute the processes themselves as the user in your config file.

Cool! It sees the new `messager-consume` group. That names comes from the key
at the top of our file. Next, run the `update` command... which would restart
any processes with the new config... *if* they were already running... but our's
aren't yet:

```terminal-silent
supervisorctl -c /usr/local/etc/supervisord.ini update
```

To start them, run `start messenger-consume:*`:

```terminal-silent
supervisorctl -c /usr/local/etc/supervisord.ini start messenger-consume:*
```

That last argument - `messenger-consume:*` isn't very obvious. When you create a
"program" called `messenger-consume`, this creates what's called a
"homogeneous process group". Because we have `processes=2`, this group will run
*two* processes. By saying `messenger-consume:*` it tells Supervisor to start all
processes inside that group.

When we run it... it doesn't say anything... but... our worker commands should now
be running! Let's go stop our manual worker so that *only* the ones from Supervisor
are running. Now,

```terminal
tail -f var/log/messenger.log
```

This will make it really obvious whether or not our messages are being handled by
those workers. Now, upload a few photos, delete a couple of items, move over and...
yea! It's working! It's actually working almost twice as fast as normal because
we have *twice* the workers.

And, *now* we can have some fun. First, we can see the process id's created by
Supervisor by running:

```terminal
ps -A | grep messenger:consume
```

There they are: 19915 and 19916. Let's kill one of those:

```terminal
kill 19915
```

And run that again:

```terminal-silent
ps -A | grep messenger:consume
```

Yes! 19916 is still there but because we killed the other one, supervisor started
a *new* process for it: 19995. Supervisor *rocks*.

Next, let's talk more about the options we can use to *purposely* make workers
exit before they take up too much memory. We'll also talk about how to restart
workers on deploy so that they see the new code *and* a little detail about how
things can break if you update your message class.
