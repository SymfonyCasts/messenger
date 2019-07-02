# Message, Handler & the Bus

Messenger is what's known as a "Message Bus"... which is kind of a generic tool
that can be used to do a couple of different, but similar design patterns. For
example... Messenger can be used as a "Command bus", a "Query bus", an "Event bus"
*or*... a "School bus". Oh... wait... that last one was never implemented... ok,
it can be used for the first three. Anyways, if these terms mean absolutely
*nothing* to you... great! We'll talk about what all of this means along the way.

## Command Bus Pattern

Most people will use Messenger as a "command bus"... which is sort of a design
pattern. Here's the idea. Right now, we're doing all of our work in the controller.
Well, ok, we've organized things into services, but our controller calls those
methods directly. It's nicely-organized, but it's still basically procedural:
you can read the code from top to bottom.

With a command bus, you separate *what* you want to happen - called a "command" -
from the code that *does* that work. Imagine you're working as a waiter or waitress
at a restaurant and someone wants a pizza margherita... with extra fresh basil!
Mmm. Do you... run back to the kitchen and cook it yourself? *Probably* not...
Instead, you write down the order. But... let's say instead, you write down a
"command": cook a pizza, margherita style with extra fresh basil. Next, you
"send" that command to the kitchen. And finally, some chef does all the magic
to get that pizza ready. Meanwhile, you're able to take more orders and send
more "commands" back to the kitchen.

*This* is a command bus: you create a simple, informational command "cook a pizza",
give it to some central "system"... which is given that fancy word "bus", and *it*
makes sure that something sees that command and "handles" it... in this case,
a "chef" cooks the pizza. And that central "bus" is probably smart enough to have
different people "handle" different commands: the chef cooks the pizza, but the
bar tender prepares the drink orders.

## Creating the Command Class

Let's recreate that *same* idea... in code! The "command" *we* want to issue is:
add Ponka to this image. In Messenger, each command is a simple PHP class. In the
`src/` directory, create a new `Message/` directory. We can put our command, or
"message", classes anywhere... but this is a nice way to organize things. Create
a new PHP class called `AddPonkaToImage`... because that describes the *intent*
of what we want to happen: we want someone to add ponka to the image. Inside...
for now... do *nothing*.

[[[ code('2679ecb5e7') ]]]

A message class is *your* code: it can look *however* you want. More on that later.

## Creating the Handler Class

Command, done! Step 2 is to create the "handler" class - the code that will *actually*
add Ponka to an image. Once again, this class can live anywhere, but let's create
a new `MessageHandler/` directory to keep things organized. The handler class
can *also* be called anything... but unless you *love* being confused...
call it `AddPonkaToImageHandler`.

[[[ code('57f1429b7c') ]]]

Unlike the message, the handler class *does* have a few rules. First, a handler
class must implement `MessageHandlerInterface`... which is actually *empty*. It's
a "marker" interface. We'll talk about *why* this is needed in a bit. And second,
the class must have a public function called `__invoke()` with a single argument
that is *type-hinted* with the message class. So, `AddPonkaToImage`, then any
argument name: `$addPonkaToImage`. Inside, hmm, just to see how this all works,
let's `dump($addPonkaToImage)`.

[[[ code('880b6eada5') ]]]

## Connecting the Message and Handler

Ok, let's back up. On a high level, here's how this is going to work. In our code,
we'll create an `AddPonkaToImage` object and tell messenger - the message bus -
to "handle" it. Messenger will see our `AddPonkaToImage` object, go get
the `AddPonkaToImageHandler` service, call its `__invoke()` method and pass it the
`AddPonkaToImage` object. That's... all there is to it!

But wait... how does messenger know that the `AddPonkaToImage` object should be
"handled" by `AddPonkaToImageHandler`? Like, if we had multiple command and handler
classes, how would it know which handler handles which message?

Find your terminal and run:

```terminal
php bin/console debug:messenger
```

This is an *awesome* command: it shows us a map of which handler will be called
for each message. We only have 1 right now, but... yea, somehow it *already*
knows that `AddPonkaToImage` should be handled by `AddPonkaToImageHandler`. How?

It knows thanks to two things. First, that empty `MessageHandlerInterface` is
a "flag" that tells Symfony that this is a messenger "handler". And second, Messenger
looks for a method called `__invoke()` and reads the *type-hint* on its argument
to know *which* message class this should handle. So, `AddPonkaToImage`.

And yes, you can *totally* configure all of this in a different way, and even skip
adding the interface by using a tag. We'll talk about some of this later... but
it's usually not something you need to worry about.

Oh, and if you're not familiar with the `__invoke()` method, ignoring Messenger
for a minute, that's a magic method you can put on any PHP class to make it
"executable": you can take an object and call it like a function... *if* it has
this method:

```php
$handler = new AddPonkaToImageHandler();
$handler($addPonkaToImage);
```

That detail is not important *at all* to understand Messenger, but it explains
why this, otherwise "strange" method name was chosen.

## Dispatching the Message

Phew! Status check: we have a message class, we have a handler class, and thanks
to some smartness from Symfony, Messenger knows these are linked together. The
*last* thing we need to do is... actually send the command, or "message", to the
bus!

Head over to `ImagePostController`. This is the endpoint that uploads our image
and adds Ponka to it. Fetch the message bus by adding a new argument with the
`MessageBusInterface` type-hint.

[[[ code('a9cdac2c6b') ]]]

Then... right *above* all the Ponka image code - we'll leave all of that there
for the moment - say `$message = new AddPonkaToImage()`. And then
`$messageBus->dispatch($message)`.


[[[ code('cac1436bb2') ]]]

That's it! `dispatch()` is the *only* method on that object... it doesn't get any
more complicated than this.

So... let's try it! If everything works, this `AddPonkaToImage` object should
be passed to `__invoke()` and then we'll dump it. Since this will all happen on
an AJAX request, we'll use a trick in the profiler to see if it worked.

Head back and refresh the page... just to be safe. Upload a new photo and... when
it finishes, down on the web debug toolbar, hover over the arrow icon to find...
nice! Here is that AJAX request. I'll hold Command and click the link to open it
in a new tab. This is the profiler for that AJAX request. Click the "Debug" link
on the left.

Ha! There it is! This shows us that our `dump()` code *was* executed during the
AJAX request! It worked! We pass the message to the message bus and then it
calls the handler.

Of course... our handler doesn't *do* anything yet. Next, let's move all of the
Ponkafication logic from our controller into the handler.
