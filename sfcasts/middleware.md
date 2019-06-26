# Middleware

Coming soon...

Internally. When you dispatch, I'm a message into the message bus. What happens? What
does the code look like inside there? The answer is there is no code inside there.
Okay, well I know this is a little confusing. What I want to talk about now is a
system called middleware. Your your, your message buses full of these classes called
middleware, and when you pass your message into the message dispatch, what it
actually does is it passes that message to the first middleware. The middleware can
then run some code on it and passes it to the second middle where it can run some
code. It passes it to the third middleware until it gets to the final one.

And then

by the nature of how a middleware works, once the most inner one returns a value,
then the code actually goes back to the middleware. See back to middleware beam, act
back the middleware a. So it's just a way middlewares on a high level are just a
little bits of code that we wrote that that run when you call dispatch and
middlewares can do lots of things. You could have a middleware that like logs what's
happening inside the system, but even the core functionality of messenger itself,
which is the handling of the execution of the handlers and also the sending of the
message to the transports are actually done by middleware. You can find them, they're
called the `SendMessageMiddleware` and the `HandleMessageMiddleware`.

Anyways, middlewares or weird little things, but they are just super powerful for
hooking into the process of, uh, of what happens when a message is dispatch and doing
stuff with it. So here's our goal. I want to, anytime that we dispatch a message from
anywhere, I want to add a middleware so that a unique id is attached to that message.
And then ultimately we're going to log what happens to that message as it goes
through various various parts of the system is a really powerful way where you can
actually track an individual message and see how long it took to be processed or how
many times it was retried, etc. So writing a middleware is actually fairly
simple. I'm going to create a new directory inside of `src/` called `Messenger/`. Then
inside of there are new phd class called `AuditMiddleware`. The only rule of the
middleware is it needs to implement surprise `MiddlewareInterface`. I'll go to Ko
Command + N or "Code -> Generate..." and "Implement Methods". And this just needs one method,
which is called `handle()`. And we'll talk about the stack thing in a second, but it
makes you're past the `Envelope` number because even if we, uh, cause that's what we
passed the dispatch. And even if we just pass our message directly, um, the message
bus wraps it in an `Envelope` and then what we need to return is that `Envelope`.

So the one line that your middleware is almost definitely going to have is this
`return $stack->next()->handle($envelope, $stack)`. With this basically says is
when our middleware is our is called, I want you to call the next middleware in the
stack and return its value. This makes sure that all the middleware, our call now
just creating this class is not enough. It's not going to automatically be used. And
actually I want you to uh, finding open terminal and run. Once again

```terminal
php bin/console debug:config framework messenger
```

And if you look at the top, there's actually you
look at the bottom, there's actually a key called buses, which defines all of the
message bus objects you have in your system, which right now we just have our one
message bus and notice there's a Canada, they're called `middleware`. That's where you
can add your own custom middleware that will be added in addition to the default
`middleware`. So I'm actually going to copy that config there. We'll go into 
`config/packages/messenger.yaml` and under `framework:`, `messenger:`. I'll put this right on top.
Let's paste those there in dent them and then we'll say `middleware:`. And we're
going to add our new middleware, which is `App\Messenger\AuditMiddleware`.

And just like that, our middleware should be called. Now to prove it is, let's do the
first part of our experiment and that's this. We somehow want to attach a unique id,
just some string to this message that stays with this message forever, whether it's
handled synchronously or handled asynchronously. And passed across multiple
transports. The way we can do that is by adding our own stamp class. So check this
out in the `Messenger/` directory. Brand new PHP class called and `UniqueIdStamp`
stamps only have one rule. They need to implement `StampInterface` though it doesn't
need any methods. That's just a little bit of a marker interface. And inside here we
can do whatever we want. The only rule about stamp is that it needs to be serializable
cause it's going to be passed along with, uh, your message across the
transports. So I'm just going to create a new `private $uniqueId;`

[inaudible].

All right. Create a new page for class called `UniqueIdStamp`. The only rules that
needs implement `StampInterface`. This is just a marker interface. Inside here we can
do whatever we want. So I'll say `private $uniqueId`. And then I'm gonna create a
`public function __construct()`. And I'm actually gonna set `$this->uniqueId = uniqid();`
Phps, uh, functions I bear. And down the bottom I'll go to Command + N
on a Mac Code -> Generate... and I will generate the gear for unique idea which will
return a `string`. So it's a nice little value object.

Now instead of our audit middleware, before we pass this to the next part of our
stack, we're going to add that stamp on top. Now one thing we need to do those, we
need to make sure that we don't add the stamp and multiple times because as we're
going to see in the second one, message can be passed to the message restaurant
multiple times. There'll be passing the message bus when we first handle it. But if
it's a sickness, it will actually be passed the message bus the second time when we
consume it. And if it's retried, it might be passed to a third or fourth time through
the, uh, through the middleware. So I'm going to say, if 
`null === $envelope->last(UniqueIdStamp::class)`,
then `$envelope = $envelope->with(new UniqueIdStamp())`
Now there's a couple of things going on here.

A envelope with his, the way that you add new stamps to an envelope, but envelopes
are something we call immutable, which means when you call it `$envelope->with()` instead
of adding it to this stamp, it actually creates a brand new stamp and returns it. So
if you just say envelope with that's not gonna work, you need to say 
`$envelope = $envelope->with()`, um, because as you can see here, clones, the object adds the stamp and
then creates a new one. There are, the thing is if you ever want to fetch off a
specific stamp, you can do that by its class name. So M and technically an envelope
can hold multiple stamps of the same class. So by saying `$envelope->last()`, you're
saying, give me the most recent unique id stamp or not. So basically this is a way of
saying, if we don't have unique ice stamp and stamp yet, let's add one down here.

We can fetch it off by saying the same thing, `$stamp = $envelope->last(UniqueIdStamp::class)`
And then I'm gonna add a little hint to my editor here that says that stamp
is a `UniqueIdStamp` at this point, unless we had a bug in our code, like we know
that this will definitely be unique ids stamp not at all. And then down below this
I'm just going to say `$stamp->getUniqueId()`. So if all successful, we should be able
to try this and actually see a dump a of the unique id inside of our, um, instead of
our message.

So let's try it. I'll refresh the page. Don't really need to do that, but just to be
safe, I'll select an image and processes and I'm going to hold you, go to the web
debug toolbar, open up the profile for that. And as you can see, the unique id that
was attached to that message and even after that message was sent to the transport,
as we're going to see in a few minutes that that is stuck onto it. We can also do
this with delete cause this is gonna work for literally any message. I can delete
something. I'll open up that profiler going to debug, and there is it's message. Now
notice it actually has two messages there. See, they're slightly different because it
dispatches the first message and then dispatches the second message inside there so
we can see that being dumped twice. Pretty cool. Next, let's actually do something
useful with this. Inside of our middleware, we're going to start logging different
things. So we're going to say that this message was just dispatched. This message was
received from our transport. This message was sent to the transport. We're going to
start logging the life cycle of what's happening with this one specific message.