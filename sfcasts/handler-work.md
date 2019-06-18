# Handler Work

Coming soon...

Incentive, our control or when we finished uploading, we are now creating a new ad,
Ponca to image object and then sending it through the message bus. And the end result
of that is that the message bus is calling our invoke method. And of course it knows
that this is a handler for the add pumpkin image. Thanks to the fact that we've added
this type pant here and it implements this interface.

Yeah.

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
unused variables, a pontificator photo manager and entity manager or all services.
And we can see that on top here. We were auto wiring those into our class. In fact,
we don't need the punk of Occator anymore.

Okay.

So the cool thing is the ad punk and image class. That's simple.

Yeah,

Mala class, this is not a service in the container, but our handler is a service
Lincoln Tanner, which means we can use dependency injection like normal. Yeah. To get
whatever we need. So I'm going to add public function_and can score, construct. And
let's see here. We need the punk of Occator. So that's actually photo punk for cater
pontificator and we need the photo manager, photo file manager, photo manager. And
the last thing we need is the entity manager here, which is going to be entity
manager, interface entity manager. Perfect. I'll hit enter and select initialize
fields to get those three things set up on top. And then we can just use them down
here like normal, this era pontificator this or a photo manager does or a photo
manager. And this->entity manager. Nice. And that leaves us with only one more
undefined variable in its the image post, which makes sense. So originally in our
image post controller we're ultimately doing is creating an image post entity here.
They supposed to entity is very simple, basically controls the file name, uh, stores,
the file name on it and some other information. This is actually what's stored in the
database.

Okay.

So when are our add pumpkin image handler? It needs to know like what uh, image am I
supposed to be adding Ponca too cause it uses it to get the file name. And again, the
specifics of this code are important. The point is that our message somehow needs to
communicate like what? Uh, file should. Punk could be attitude. So this is the great
thing about the message class. This semester's class can look however we want. And
right now we've discovered that our handler needs to know the image post object that
it needs to work on. So no problem. Let's add a public function underscore,_construct
over here, I'm going to give us one argument, which is going to be image post.

Okay,

image post. I'll enter it again here.

Okay.

And then down here I'm actually gonna add a setter for it.

Okay,

so public you can get an image post, which is going to return an image post their
return, this->image post.

And honestly, we could have done all this however you want and get out and get on a
set every wanted to. We could have made this a public property, you can cop do, you
can do what ever you want with this class. But this is a really nice pattern because
now it forces me instead of my image posts controller. As you can see down here that
I now have to pass the image post to the message object. And then of course when we
get over here to our handler, when our handlers called, you can barely, we can say
image post = add punker to image. Get image post.

Yeah.

And it communicates it forward. So that's the real power of the message class. It
really is like you are writing a message to someone that says, I want you to do a
task and here's all the information that you need to know to do that task. And then
you hand it off to the message bus and that it calls the handler in the handler has
all the information it needs to do that work. It's a really cool idea. All right, so
let's make sure this works. I'll go over and refresh just to be safe.

Okay.

Well upload a new image.

Okay.

And got it. It is still working.