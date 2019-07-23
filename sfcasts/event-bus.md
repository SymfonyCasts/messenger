# Event Bus

Coming soon...

Messenger is what's known as a message bus. We've talked a little bit of about
that, but it turns out that the term message in computer science is kind of this
generic thing and it turns out that there are two types of messages in two types of
message buses. The first one is a command bus and that's what we've been doing so
far. This is where you create classes that are a command `AddPonkaToImage` or 
`DeleteImagePost`. They sound like you're commanding someone to do something and when you
create classes that look like this, these are called commands and messenger becomes a
command bus purely because you are using it as a command bus and one of the
properties of command and buses is that there's always, every one command class
always is mapped to exactly one handler. That's just how that pattern is done.

You know when you're writing it that this class will have a handler but and it will
only have one. The second type of message is called an event and the second type of
message bus is called an event bus. And the big difference with the event bus is that
your, your classes, which are now called events will have zero too many handlers.
We're going to talk more about, um, why and how it's used in a second. There is
actually a third type of message and a message buses all the query and a query bus.
We'll talk about those a little bit later. The point is the difference between
commands and Events and command buses and event buses can be confusing. So I wanted to
actually show them in action so you can see the difference and see that they're
really kind of just the same thing. Um, ultimately they come down to using messenger.
There's one tool to leverage two different but similar design patterns.

So currently we have, inside of our container we have one message bus service. And as
I mentioned, we're using it as a command bus. Now what I want to do is in 
`config/packages/` I'll quote close a couple of directories can pick back, just 
`messenger.yaml`. We're actually going to configure a second bus, which we are going to use as
an event bus. So under the `buses:` to here, I'm going to create a new one called 
`event.bus` and I'm just gonna set that the `~` which is null, um, just because
we don't have any other configuration that I need to put under the name of this
event, that boss isn't important. You'll see how that's used in a second

and you'll see how that's used in a second. Now, so far for our one bus, whenever
we've needed it somewhere like an `ImagePostController`, we've just autowire it by
using the `MessageBusInterface` type hint. So the question is how can we get access
to this new service that's apparently in the content? Well, move over. Mover
determined terminal and run 

```terminal
php bin/console debug:autowiring
```

and I will actually fail.
Does invalid configuration for path `framework.messenger`? You must specify the
`default_bus`. If you define more than one bus, I'm going to copy that default bus
key. What this is telling us is right under messenger. Now that you'd say `default_bus`
and set this to `messenger:` dot bused on default. What that does is that that tells
messenger if we just type hint a, if we use the `MessageBusInterface` type in which
of the two bus services should I autowire to you?

The `default_bus` says we should continue auto wiring. The same boss that's been auto
wiring this entire time and I have to go back and run. Do you have auto wiring again,

```terminal-silent
php bin/console debug:autowiring
```

I'm going to search up in here for a mess. There we go. We can see that there are now
two entries for two different services and what it's saying here is it says if you
want the main message bus, you can just type in `MessageBusInterface` and the service
set points do. It's got some debug stuff on it, but you can see how it's 
`messenger.bus.default`. If we want to get access to this new event bus service, what we
can do is we can actually use the same type end and then also call the argument of
`$eventBus`. This is a new feature in Symfony 4.2 and it's a really nice way for us to
have multiple message buses in the system and we can still autowire them. One of
them is just the type end. But if we use that type hint with that exact argument
name, then it's going to pass us through the event bus. But what is the difference
between these two buses right now between these two? A message buses? Like why do we
even have two of them and do they behave differently? The answer actually is no.

A bus is really nothing more than a set of middleware. And we know that there are
some really important middleware internally that will handle the message and also
send the message to the transport. Um, but they're really, there's no other way that
a bus can different cause it's just a collection of middleware right now the only
difference between these two buses is that for the first boss, we've added our 
`AuditMiddleware`. So technically the event bus doesn't have that and that's the only way
that they're different. Um, we and of course if we want it to we can add that 
`AuditMiddleware`  to event bus. That's something we're going to do later when I'm talking
to the point is, I'm trying to say here is that these two services behave identically
and in fact if we wanted to we could use this message bus default the first one as we
could actually send commands into it and events into it. The only reason I'm
splitting those up into two is just to be a little bit more organized. A bus is just
a messenger bus is something that okay this term doesn't completely make sense yet.
That's fine cause I want to get into what events and event handlers actually look
like.

Yeah. So let me give you an example.

Let me give you an example of a situation. Suppose that you have something in your
site where a user registers and when that happens you do three things. You Save the,
you as new user to the database, you send them an email and then you, and then you
add them to some CRM system. Now, depending on how your code looks, you might have
all of that code in your controller or hopefully you have all of that code in a
service. Or if you're using messenger, it's likely that you have all of that code
inside of a handler. So you're doing three things inside of your handler, uh, inside
of your handlers service.

And that's really fine. But if you want to get kind of technical with it, um, that
class suffers from a couple of problems. Um, first if you need to do something, if
you need to do a fourth thing after the user registers, then you need to actually go
into that one class and add the code there. So your code is kind of not very
decoupled. Um, and also your, you know, your Po, your service that your handler that
has all that code violates these single responsibility principle cause it's actually
doing a whole bunch of unrelated things. So in event buses, a pattern to help with
this. And the idea is that if you have a command like `AddPonkaToImage` and a handler
like `AddPonkaToImageHandler`, your handler should only do the primary task, not any
secondary tasks. So in my example of a user registering, if we had a

um,

a handler for a, we had a Register User command message, its handler should probably
only save the item to the database. It shouldn't also send the email and add the, add
the user to the CRM system. What it should do instead is it should it, it should say
the user, the database. And then it should create an event and dispatch that event,
that event class maybe user was registered, would have two handlers, one handler
would send an email in, the other handler would add the user in the CRM. So your
command handler does the primary task and then if it needs to in dispatches an event
and then we'll have zero to many a hand handlers to that event to do these secondary
tasks. So this is all about a pattern really for as far as messenger is concerned.
They look no difference. Messenger allows each message to have as many handlers as
want, but then we can leverage that one piece of technology to do these two different
types of patterns.

And actually we already have a situation like this. If you look at, uh, 
`DeleteImagePost` and then `DeleteImagePostHandler` inside of here, the main thing that 
`DeleteImagePostHandler` does is it actually removes the `ImagePost` from the database. And
that is the primary task that it's doing, but it also has a secondary task that it
needs to delete the file from the filesystem. This is a perfect example of where we
could dispatch something through any bedbugs. And we actually kind of already did
that, but instead of dispatching another event, this, this `DeletePhotoFile` is
really a command and it sounds like the command says `DeletePhotoFile`. So next,
we're actually gonna read factor a, the bottom this year to actually be truly have an
event and event handlers. Um, and then talk about it. A couple other things.