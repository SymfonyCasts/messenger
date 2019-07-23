# Deploy

Coming soon...

We need to talk about what happens on production and part of it is really simple
actually. Basically Sun out on production. We just need to make sure that this
command, `messenger:consume` with whatever transports we got to consume is
running always. And with some hosting platforms like a platform as a service, it's
like like Symfony cloud. This is just a matter of configuration. You can basically
say, hey, I want to make sure that this command is always running, but if you don't
have an environment like that, you need to do something different. It actually, it's
not just that we need to have this command, I'm always running really, we don't want
this command to run forever. Nature PHP scripts is, no matter how well you write
them, there's probably going to be memory leaks. So eventually this is probably gonna
run out of memory and it's going to die. And in fact, that's exactly what we want.
What we really want is we want `messenger:consume` to run, handle a few messages, kill
itself, and then have something else. Make sure that every time this process
disappears that it's restarted. The tool to do that is called supervisor supervisors.
Just just a tool that's available on unix space machines and its job is exactly that.
You basically tell it I need to run this process and if you ever see that this
process has stopped running, start it again.

So we're not going to go super deep into supervisor, but we are going to get this set
up. No, I'm using a Mac. So I installed supervisor via Brew upon to you. You can use
an app yet. It's a very common tool to run supervisor. You're gonna need a
configuration file, smashing a search for, um, Messenger Symfony, find the, uh,
actual main and messenger documentation here. And we actually have a spot on here
that talks a little bit about a supervisor configuration. So let's copy this
configuration here. We don't need that first line. That's just a comment. And you can
put this anywhere. I'm actually going to, I want to commit this to my, I'll put it in
the `config/` directory and I'm going to call this `messenger-worker.ini` and then
paste that stuff in there. This is a configuration file. It's going to tell
supervisor, um, what command to run. And it's also going to give it all their
information, like what users should run as and the number of processes. So this is
actually going to create two workers because the more workers you have, the faster
messages on the process.

This is technically server configuration, so you might not commit this to your
pository if you have some other way of setting up your infrastructure. Um, if you
don't, I like to commit it to my project and you'll see how we'll get supervisor to
use this in a second. So this is something supervisor, something that we're gonna use
on production only. We're not going to use it on our local computer cause I don't log
on computer. We can just run messenger, consume ourselves. But for the purposes of
this screencast, we're going to pretend like, like my computer right now is
production and we're gonna get supervisor running locally.

So the person I'm gonna do is actually change this path here. So for me this is 
`/Users/weaverryan/messenger`, which if you're not sure you can head over to project here.
Perfect. Nope. So yeah, I forgot my `Sites/` part. And then down here I'm going to
change when you use her to be `weaverryan` cause that's who I am. And if you look
closer to the command, it's actually running `messenger:consume async`. And so we need
to make sure that we also had `async_priority_high`. And then there's also an optional
on the ad that says `--time-limit=3600` which means that this command is
actually only going to run for six minutes and then it's going to exit.

I want to talk more about some of those options later, but that's actually perfect
because that's going to help prevent it from running out of memory. And as soon as it
exits supervisors in a notice that that process is missing. It's just going to
restart it fresh. It's really, really nice. Once you have this configuration file
done, you need to point a supervisor. Uh, you need to make sure that supervisor can
see it. Now, because I'm using, because I'm using brew on a Mac, um, also supervisor
always uses a configuration file. When you use a brew on a Mac, it's located at 
`/usr/local/etc/supervisord.ini`. And somewhere inside of here you're going to see
something like this in the bottom where it says, I'm looking for all of my
configuration files in this specific path. So what I'm going to do is I'm actually
just, I'm going to create a symbolic link. So I'll say 

```terminal
ln -s ~/Sites/messenger/config/messenger-worker.ini /usr/local/etc/supervisor.d/
```

I now I'll paste that path.
Perfect. And now I have one file inside of that directory or supervisor knows to look
at. That's actually run supervisor. You used something called supervisor control and
on an Ubuntu System, um, you can just all you're going to do say `supervisorctl`
and with brew you actually need to add a `-c` option, which and then pointed at the
configuration path so that it's looking in the right spot. So you may or may not need
that dash c configuration whenever you run new supervisor control commands. But here
we say `reread` that basically tells it to look for new configuration. 

```terminal-silent
supervisorctl -c /usr/loca/etc/supervisord.ini reread
```

You can see a season, a new `messager-consume` group here. This is actually reading 
this a key at the top here. And then we can say 

```terminal
supervisorctl -c /usr/loca/etc/supervisord.ini update
```

And then to actually start the
process, we're going to say `start messenger-consume:*`

```terminal-silent
supervisorctl -c /usr/loca/etc/supervisord.ini start messenger-consume:*
```

Now the last part is a little bit confusing. What's happening here is when you have a
program called `messenger-consume`, that creates what's called a a process group.
And since we have number of processes = to two, it's actually gonna create two
processes. So by doing this `messenger-consume:*`, it says create all of
the processes under that group. So I'll hit that and it doesn't show anything, but
actually I'm going to go over here and stop my main worker so there's no worker's
running except for the ones that buy supervisor. I'll do 

```terminal
tail -f var/log/messenger.log
```

form. That's the configuration file that we created earlier to just
kind of like log some messages and now let's go over here and do some stuff.

I'll put a couple of photos, bleed a few things, move over and yes it is working.
It's actually working twice as fast as normal because we actually have twice the
workers. The really cool part about this though is a is is watching how you can kill
the workers. So I'm going to say 

```terminal
ps -A | grep messenger:consume
```

What I'm doing is
I'm grabbing all the processes for things that have this messenger consuming it and
perfect. You can see two of them. This last one was just the graph. So processes 
19915 and 19916 and I can actually kill one of those 

```terminal
kill 19915
```

Run that again 

```terminal-silent
ps -A | grep messenger:consume
```

and you can see 19916 is still there, but it's out supervisor actually
started a new one, that 19995. So this is the ideal setup to make sure those
processes are running.

Now the two of the things you need to know about our one run 

```terminal
php bin/console messenger:consume --help
```

As we saw earlier, there is an option on here called `--time-limit`
which basically says only run for six minutes and then exit yourself. Um, you
may also want to add the `--memory-limit` or maybe even the message `--limit` on there. The
key thing is that we really want to make sure that we don't run our 
`messenger:consume` workers so long that it runs out of memory in the middle of executing a
specific, um, uh, handling a specific message and it's okay to be aggressive and, uh,
and kill them fairly often. They're pretty, these processes are pretty cheap to
start. So, yeah.

Now the last piece here is that you remember that so far we've been, every time we
make a code change to one of our handlers, we have needed to manually restart
`messenger:consume` so that the workers see the new code. Cause once you start a, once
you start a worker, command a reads all of your PHP code at that moment. So if our
PSB code changes on a deploy, all of our worker processes aren't going to see it
until they kill themselves. And we started, which could happen up to six minutes from
now so that it's totally not okay. We need, when we deploy, we need our code to be
seen immediately. What that means is that at the moment we deploy, we need to kill
all of our processes. Fortunately there's a really easy way to do that with
Messenger. So I'm actually going to go back over here and a once again, look at,
we're on `ps -A` so we can see our worker processes.

```terminal-silent
ps -A | grep messenger:consume
```

One of the things that you can do is on deploy, run 

```terminal
php bin/console messenger:stop-workers
```

[inaudible] for that. And then we're on PSD chat again.

```terminal-silent
ps -A | grep messenger:consume
``` 
 
There you
go. You can say to new processes. So what this happens behind the scenes is this
actually sends a signal to each of the individual workers that they should exit and
what they do is they don't exit like in the middle of handling a message, but they'll
finish handling whatever message you're handling right now and then they will access.
So it's a graceful exit and so you can just make that part of your deploy process and
all the workers are going to gracefully exit.

Now the one last detail I want to mention about Diplo deployment is this because of
the nature of messenger being asynchronous, we have to keep something in mind. Let me
look at one of our message objects here. How about `AddPonkaToImage`. So suppose
that our site is already currently deployed and our `AddPonkaToImage` class and it
looks like this. So we somebody uploads an image, we serialize this and we send it to
our transport. Now suppose that before we handle that, while that's sitting inside a
transport, we deploy a new version of our sites. Unless that new version of our site,
we've refactored this class here and maybe we've renamed this `$imagePostId` property
to `$imagePost`. Well the problem is that when we re read that `AddPonkaToImage`,
that message from the in deserealize it, it's going to be serialized in the format of
the old class meeting. It's going to be deep, it's going to be serialized and that
property is going to be `$imagePostId`. That's one that's DC realize into our new
class. Things might not match up. In that case, a image post would be a uh, would be
empty. So this is something you just need to keep in mind. If you do refactor your
message classes,

and you know that you're going to have some of those messages already in the queue,
you need to do it in a way where you kind of keep the old property temporarily and
use it as a fallback and then start using the new and then start using the new
property. And then after just one deploy, once your mess, you can just be moved. Then
you can get rid of the old stuff. All right, so that's it for deployment. Um, next
we'll talk about something else. Now, before I continue, as cool as all this stuff
is, I'm actually gonna go back over here.

Okay.

Look over here and run. 

```terminal
supervisorctl -c /usr/loca/etc/supervisord.ini stop messenger-consume:*
```

You'd see it stops. There's two processes there. Um, and I'll go back to running our
`messenger:consume` manually, 

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

just cause it's a little bit easier when we're
developing locally because we can actually see what's going on.