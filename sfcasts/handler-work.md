# Handler Work

Coming soon...

Incentive, our Controller when we finished uploading, we are now creating a new 
`AddPonkaToImage` object and then sending it through the message bus. And the end result
of that is that the message bus is calling our `__invoke()` method. And of course it knows
that this is a handler for the `AddPonkaToImage`. Thanks to the fact that we've added
this type hint here and it implements this interface.

The real goal of having a message class in a handler class though is for the handler
class or do all of the work of adding Ponka to the images. And right now that's done
over here inside of our controller. So let's actually copy all of this logic here.
Uh, this first line here is actually what ads Ponka to the image. This, we then use
another service to actually save the new contents over the existing file. We then
column method on the image post to actually update a database field that sends one
punk was added and finally at the bottom we've flushed those changes, the database.
So, um, there's only a few lines of code here, but we're actually doing quite a lot
of work. Sorry, take those out of there. I'll delete my comments and then we're going
to move that over into our handle a class immediately. You can see we have some
unused variables, a `$ponkaficator` `$photoManager` and `$entityManager` or all services.
And we can see that on top here. We were autowiring those into our class. In fact,
we don't need the `$ponkaficator` anymore.

So the cool thing is the `AddPonkaToImage` class. That's simple.

Mala class, this is not a service in the container, but our handler is a service
Lincoln Tanner, which means we can use dependency injection like normal. Yeah. To get
whatever we need. So I'm going to add `public function __construct()`. And
let's see here. We need the `$ponkaficator`. So that's actually 
`PhotoPonkaficator $pontificator` and we need the `$photoManager` `PhotoFileManager $photoManager`. 
And the last thing we need is the `$entityManager` here, which is going to be 
`EntityManagerInterface $entityManager`. Perfect. I'll hit `Alt + Enter` and select initialize
fields to get those three things set up on top. And then we can just use them down
here like normal, `$this->ponkaficator` `$this->photoManager` does or a photo
manager. And `$this->entityManager`. Nice. And that leaves us with only one more
undefined variable in its the `$imagePost`, which makes sense. So originally in our
image post controller we're ultimately doing is creating an `ImagePost` entity here.
They supposed to entity is very simple, basically controls the filename, uh, stores,
the file name on it and some other information. This is actually what's stored in the
database.

So when are our `AddPonkaToImageHandler`? It needs to know like what uh, image am I
supposed to be adding Ponka too cause it uses it to get the filename. And again, the
specifics of this code are important. The point is that our message somehow needs to
communicate like what? Uh, file should. Ponka could be attitude. So this is the great
thing about the message class. This semester's class can look however we want. And
right now we've discovered that our handler needs to know the `ImagePost` object that
it needs to work on. So no problem. Let's add a `public function __construct()`
over here, I'm going to give us one argument, which is going to be `ImagePost $imagePost`.

I'll enter it again here. And then down here I'm actually gonna add a getter for it.
so `public function getImagePost()`, which is going to return an `ImagePost` their
`return $this->imagePost`.

And honestly, we could have done all this however you want and get out and get on a
set every wanted to. We could have made this a `public` property, you can cop do, you
can do what ever you want with this class. But this is a really nice pattern because
now it forces me instead of my `ImagePostController`. As you can see down here that
I now have to pass the `$imagePost` to the message object. And then of course when we
get over here to our handler, when our handlers called, you can barely, we can say
`$imagePost = $addPonkaToImage->getImagePost()`.

And it communicates it forward. So that's the real power of the message class. It
really is like you are writing a message to someone that says, I want you to do a
task and here's all the information that you need to know to do that task. And then
you hand it off to the message bus and that it calls the handler in the handler has
all the information it needs to do that work. It's a really cool idea. All right, so
let's make sure this works. I'll go over and refresh just to be safe.

Well upload a new image. And got it. It is still working.