# Message Class

Coming soon...

Messenger is what's known as a Message Bus, which is kind of this generic tool

that can be used to do a couple of different patterns and we're going to talk about
all of them. They are Command Buses, Query buses and Event buses and they all kind
of have subtle differences, but they all kind of are the same basic idea. The first
one and probably the most common one is going to be the command bus. Many of the
command bus is that instead of just doing work inside of your controller, or even if
we moved all of this into a service, instead of just doing work in one place, what
you're gonna do is you're going to create first a Message and then second a Message
Handler. So this idea of, of um, I've, uh, first creating a message which sort of
describes your intent of what you want to happen. Like I want to add Ponka got to an
image and then having something else that reads that message and does the work.
That's called a command Bus pattern. And it's just a nice way to organize your code.
Not even thinking about asynchronous or anything like that. So here's how


command bus pattern works inside my `src/` directory. It doesn't matter anywhere, but
I'm going to create a new `Message/` directory inside of there. A new class in this
class can be called anything I want and they can look like anything you want. This is
our message class or command class. I'm going to say `AddPonkaToImage` cause
that's the, that's describing the intent of what we want to have happen and written
here instead of here, I'm going to do nothing else right now it's just going to be an
empty class. As you'll see in a few minutes, this class can contain any information
that we want to tell to the handler. Okay, so step two was always then did create a
handler that's actually going to do the work. So once again, this class and go
anywhere, I'm going to put it in instead of a `MessageHandler/` directory, this class
can be called anything. 
They'll commonly, it's going to be called `AddPonkaToImageHandler`


In this pattern is usually one message class is connected to one handler specifically
and for reasons that I'll explain in a second. The handler class needs to implement
`MessageHandlerInterface` and that's actually a um, a marker interface. There's
nothing inside of it. It doesn't actually make us have to implement any methods. One
method that you are going to need to actually make this work is 
`public function __invoke()` and very importantly that needs to accept a one
argument which needs ie the exact type hint of the message class. So 
`AddPonkaToImage $addPonkaToImage` instead of here. I'm just going to `dump()`. 
So the idea is on a very high level, what we're going to do inside of our 
code is we're going to create an `AddPonkaToImage` object and then we're going 
to tell messenger to process this. And when it does that messenger is very simply 
going to take this object and it's going to call our `__invoke()` method and pass it here. 
And then we're going to do the work, sort of separating the intent from the actual work.

Now just by creating those two classes, you can already go to over to your terminal
here and run 

```terminal
php bin/console debug:messenger
```

And that's going to tell you all of
the messages in the system that it sees and who handles them. So it says, look, if
you dispatch an `AddPonkaToImage`, it's going to be handled by `AddPonkaToImageHandler`
Now there's two reasons. The way the way that works is because of this
`MessageHandlerInterface` that allows Symfony to basically know that this is a
message handler. And the other thing it does is it looks for a method called
`__invoke()`, and it reads the type hint. So Symfony knows that this handle,
this class handles and `AddPonkaToImage`, thanks to this type. And you can totally
customize this and call the method something else. But most of the time this is where
you're gonna want to do. And it means you don't need any extra configuration. Now, if
you're not familiar with the `__invoke()`, it's not really going to be important
here, but that's actually a way where you can, if you have a class that has
an `__invoke()` method that you then you can actually execute your class like a
function, not actually important here, messenger and Messenger. We just decided that
this would be the name of the method that your handler should have by default.

All right, so we have our message class and we have our message handler. The last
thing we need to do is actually tell a messenger, hey, I want you to do dispatch my
message. So we're going to do that over here in `ImagePostController`. So here's our
endpoint that actually uploads the image and then adds Ponka to it. So the Messenger
ads one service to our system, which you can autowire via `MessageBusInterface`.

Then down here, right above the punk of stuff, we will leave all of our existing
logic loan for a second. I'm going to say `$message = new AddPonkaToImage()` and
then `$messageBus->dispatch($message)`. And that's it. That's really, that's the only
method on message, on message bus. So if everything worked correctly, this is
actually the message bus and we'll see that we're passing an `AddPonkaToImage` and
then simply call our handler. It's a really simple idea, just separating the intent
of what we want to do from the actual work. All right, so let's try it back over
here. I'll refresh the page just to be in case, just be safe.

Then I will click to add on the thumbnail. When that finishes down here in my web, do
you have to have our, I can see the uh, uh, profiler for that. So I'm going to hold
command and opened that. And then down here, cause I dumped something in debug. There
it is. You can see that the handler was called and it does have our `AddPonkaToImage`.
So that's a really, really simple setup. What we're missing now is though we actually
need to do the work inside of here. So our end goal is actually going to be to take
out all of this code from our controller that previously did that work and move it
into our handler. Let's do that next.