# Doing Work in the Handler

Inside our controller, after we save the new file to the filesystem, we're creating
a new `AddPonkaToImage` object and dispatching it to the message bus... or technically
the "command" bus... because we're currently using it as a command bus. The end result
is that the bus calls the `__invoke()` method on our handler and passes it
that object. Messenger understands the connection between the message object
and handler thanks to the argument type-hint and this interface.

## Command Bus: Beautifully Disappointing

By the way, you might be thinking:

> Wait... the *whole* point of a "command" bus is to... just "call" this
> `__invoke()` method for me? Couldn't I just... ya know... call it myself and
> skip a layer?

And... yes! It's *that* simple! It *should* feel completely underwhelming at first!

But having that "layer", the "bus", in the middle gives us two nice things. First,
out code is more decoupled: the code that creates the "command" - our controller
in this case - doesn't know or care about our handler. It dispatches the message
and moves on. And second, this *simple* change is going to allow us to
execute handlers asynchronously. More on that soon.

## Moving code into the Handler

Back to work: all the code to add Ponka to the image is *still* done inside our
controller: this gets an updated version of the image with Ponka inside, another
service actually *saves* the new image onto the filesystem, and this last bit -
`$imagePost->markAsPonkaAdded()` - updates a date field on the entity. It's only
a few lines of code... but that's a lot of work!

Copy all of this, remove it, and I'll take my comments out too. Paste all of
that into the handler. Ok, no surprise, we have some undefined variables.
`$ponkaficator`, `$photoManager` and `$entityManager` are all services.

[[[ code('d82713772d') ]]]

In the controller... on top, we were autowiring those services into the controller
method. We don't need `$ponkaficator` anymore.

[[[ code('8f2be01f38') ]]]

Anyways, how can we get those services in our handler? Here's the really cool thing:
the "message" class - `AddPonkaToImage` is a simple, "model" class. It's kind of
like an entity: it doesn't live in the container and we don't autowire it into
our classes. If we need an `AddPonkaToImage` object, we say: `new AddPonkaToImage()`.
If we decide to give that class any constructor arguments - more on that soon - we
pass them right here.

But the *handler* classes are *services*. And *that* means we can use, good,
old-fashioned dependency injection to get any services we need.

Add `public function __construct()` with, let's see here,
`PhotoPonkaficator $ponkaficator`, `PhotoFileManager $photoManager` and... we need
the entity manager: `EntityManagerInterface $entityManager`. 

[[[ code('da447338b5') ]]]

I'll hit `Alt + Enter` and select Initialize Fields to create those properties and set them.

[[[ code('ca216a6549') ]]]

Now... let's use them: `$this->ponkaficator`, `$this->photoManager`,
`$this->photoManager` again... and `$this->entityManager`.

[[[ code('0cc8d75375') ]]]

## Message Class Data

Nice! This leaves us with just one undefined variable: the actual `$imagePost`
that we need to add Ponka to. Let's see... in the controller, we create this
`ImagePost` entity object... which is pretty simple: it holds the filename on
the filesystem... and a few other minor pieces of info. This is what we store
in the database.

Back in `AddPonkaToImageHandler`, at a high level, this class needs to know *which*
`ImagePost` it's supposed to be working on. How can we *pass* that information
from the controller to the handler? By putting it on the message class! Remember,
this is *our* class, and it can hold *whatever* data we want.

So now that we've discovered that our handler needs the `ImagePost` object, add
a `public function __construct()` with one argument: `ImagePost $imagePost`. I'll
do my usual Alt+Enter and select "Initialize fields" to create and set that property.

[[[ code('0cd52ae71b') ]]]

Down below, we'll need a way to *read* that property. Add a getter:
`public function getImagePost()` with an `ImagePost` return type. Inside,
`return $this->imagePost`.

[[[ code('0cd52ae71b') ]]]

And really... you can make this class look however you want: we could have made
this a `public` property with no need for a constructor or getter. Or you
could replace the constructor with a `setImagePost()`. *This* is the way
I like to do it... but it doesn't matter: as long as it holds the data you want
to pass to the handler... you're good!

Anyways, now we're dangerous! Back in `ImagePostController`, down here,
`AddPonkaToImage` *now* needs an argument. Pass it `$imagePost`.

[[[ code('e456246d3e') ]]]

Then, over in the handler, finish this with
`$imagePost = $addPonkaToImage->getImagePost()`.

[[[ code('638edb1f3f') ]]]

I love it! So that's the *power* of the message class: it really *is* like you're
writing a message to someone that says:

> I want you to do a task and here's all the information that you need to know to
> do that task.

Then, you hand that off to the message bus, it calls the handler, and the handler
has all the info it needs to do that work. It's a simple... but really neat idea.

Let's make sure it all works: move over and refresh just to be safe. Upload a new
image and... it still works!

Next: there's already one other job we can move to a command-handler system:
*deleting* an image.
