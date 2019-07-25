# Query Bus

Coming soon...

The last type of message bus that your common they're going to hear it you hear about
is a query bus, which it shows here. I will tell you up front that I am not a huge
fan of queer buses. I think they can make your code a little bit unnecessarily
complex for the benefits. That being said, I want you to under at least understand
what they are and how they kind of fit into this methodology. So following in what
we're doing so far and config messenger dot Yam and we already have a command bus and
an and an event bus. So let's add a query bus and keep things simple. I'll just put
um, colon till date. Now we already understand the purpose of a command or a command
bus. We actually issue commands and our messages actually sound like commands, like
add pumpkin image and delete image post. And with a command bus, every command has at
least as exactly one handler and that handler performs that work, but it doesn't
return anything. It just performs work. One property of command bosses is that they
can be synchronous or asynchronous depending on what you need to do, but some
handlers are handled synchronously or asynchronously, like our ad punk and image
handler.

Okay?

Inquiry bus is used when instead of e instead of wanting to do something, you
actually want to get information back. So for example, we're gonna, we're gonna
pretend like on our homepage here we want to print the number of messages, the number
of photos that we've uploaded. So that is actually a question we want to ask. This
system's a query. Want to ask to our system? How many photos have we been uploaded?
And to do that, we're going to use a query class. So inside the message directory,
I'm gonna Create a new query directory. Inside of here, I'm gonna create a new PHP
class and it's going to say get total image count. So you can see, it sounds like a
query. I want to get the total image count.

And actually we're going to leave this blank because this doesn't need a, we don't
need to pass any parameters to this. We just want to get the total image count of the
entire system. Now, inside of a message handler, I'm going to do the same thing. I'm
going to create a query directory and here I'm going to create a class that's called,
it gets total image count handler and like everything else, this is gonna implement a
message, handle or interface. We'll do public function underscore, invoke what the
type end four get total image count, get total image count. And here this is where I
would normally make a database query or something like that. I'm just gonna Return 50
I'll leave that actual part up to you, but hold on a second cause we already did
something crazy. I'm returning a value that is not something that I have done from
anywhere else. Normally commands just do work, they return the value. But here I am
actually returning a value that is the difference between a query and a query
handler. Also I'm going to go before we actually dispatch this message, I'm gonna go
to services .yaml and because we're keeping our command event, inquiry buses really
organized, I will paste on another one of these import lines here. Um,

so that the, anything in the query directory is only sent to the query bus. Cool. So
for an over on Ben Counsel debug.

Okay

Messenger you can see, yep. Queer bus has this one event, bus has this one and
command bus has these too.

All right, so let's actually use this. And this is actually going to go into our
controller main controller cause this is what renders our homepage. So we're gonna
start the same way. We need to actually get that query bus instance. Now you remember
if you were on vim console, debug auto wiring and I'll search that for mess. You can
see that the main message bus, the main command bus, you can, we'll respond. We can
get that by just type any message by interface. Or if we want the event bus we have,
we can call it event bus or the query bus, we can call it query bus. So here we can
say a message, bus interface, query bus.

Okay.

And that will give us that query of us. And then down here, I'm actually just going
to say envelope = query bus->dispatch knew get total image count. Now remember we
haven't used it too much, but when you dispatch, uh, when you called dispatch on the
bus, it will actually return to you the final envelope

[inaudible]

that represents that image and that envelope will probably have a number of different
stamps on it. And in fact, once a, uh, once a message has been handled and with query
buses, we're gonna always make sure that our messages are handled synchronously. Once
a message is handled, it's going to have a handled stamp on it. So I'm going to say
handled

= envelope,->last and then handled stamp ::class now below inline documentation above
that so that can advertise this as a handled stamp. And then to get the result of
your handler that's actually stored on that stamp. So I can say image count =
handled->get result. So to make sure that's working, I'll pass it into my template as
an image count variable. And then in my template templates, main homepage, the html
twig [inaudible], however, everything is built via UJS. So let's actually just for
simplicity, we'll actually add this as a title to our page. We'll say punk, good
image count photos. That's it. Move over, refresh and it works. Punk, good 50 photos.
So query your buses as I mentioned are my favorite. Um, cause we're not guaranteed.
Like what type of this returns. If it's a string or an object, it's a little indirect
and you also can't take care of it. Can't take advantage of asynchronous. You know,
querying buses are meant to be synchronous. So it's not like you're saving
performance any of this. But if you like this pattern you can totally use this. And
on the Symfony documentation, they actually

somewhere inside of here, back on the documentation. If you go back to the main
messenger page and go all the way at the bottom, there's actually a thing in here
called, um, getting results from your handler. And it includes some shortcuts that
you can take down here, uh, that we're not going to go through that can make getting
the, uh, return value a little bit easier because, you know, this, hopefully it makes
sense to you, but it's not that easy. So that's it. So now we know, like, why is
messenger is called a message bus? But it can really be used as a command bus, an
event bus, or a query bus. And those are all really just the same things. They're
just kind of different ways to use this pattern. So I don't overly stress out about
it. Um, command buses are use, use, uh, whatever's useful, et Cetera, et cetera.