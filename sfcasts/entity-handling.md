# Entity Handling

Coming soon...

We're currently experiencing a very strange issue. We know that ad pocket, the image
handlers being called successfully by the worker process because it's actually adding
Pumpkin to the images. But for some reason this image posts, which is our entity aero
mark, as Ponca added, which sets the Ponca added, that property is not being saved to
the database and the database, even after the image images added, punk added, that is
still not. So if we're updating the property and then calling this entity marriage
managerial flush, like why isn't that working? Well what they might try to debug
this, as you might say, I don't know, do I need to call this entity manager->persist
first? I, in theory, you shouldn't need to do that because persist is only needed to
persist new objects. If you query for an existing object, you should just be able to
flush and it will find it and save it. But let's try this and see what happens. So
I'm gonna move over here and we'll select a new image.

And over here, yeah, done that.

So let's find out adding this persist makes any difference. Now before we do this,
remember this code is going to be executed by our worker process. And there's a
tricky thing with work processes. They run forever, but as soon as you start a worker
process, if you make changes to any of your PHP code, your work process doesn't see
it. It's already loaded PHP and has PHP in your memory. So anytime you make a change
to code, that's going to be executed about one of your by, by your worker, you need
to go over and actually restart that worker saw, control c and then run messenger,
call and consumed Dash V. V we're going to talk later about deployment and how you
can automatically restart your workers in a safe way on deployment to make this
happen. But during development, at least right now, you need to know that you need to
do that. Alright, so let's flip back over here and let's upload a new file and then
we'll go over here and you can see it was handled successfully.

And if I go back and refresh the front page, something very strange just happened. We
have three things that showed up. Okay one of them without the day one of them with
the day and something that's totally broken for your over and look in the database. I
think I fucked this up. Keep text here about restarting the worker. So let's go over
and upload a new photo and see what happens this time. Okay. It opens it processes
just fine. If we go over and look at our worker. Yeah, move received the message just
fine. So let's see if that worked. We refresh hand. Whoa. There are two rows in the
database, one with the date set and one without the date set. We can see this if we
go and look at our database right here.

Yeah, there are two rows here that actually pointed ting to the exact same files if
it duplicated it. And actually that's exactly what happened. So I wanted to show you
this because it's a very confusing situation. It's also going to touch on a best
practice, um, for your messages internally. Whenever you call one of you query for an
entity and doctrine, it keeps a list of all the entities you queried from. Then when
you call flush, it actually loops over all those looks for changes and if it finds
any, it makes updates when you have a new object even call persist on it. And all
that does is it really tells doctrine, hey, be aware of this object so that when you
call flush later in how sees the object sees that it's an object that it didn't query
from and determines that it should insert it into the database.

So what's happening here is that when our worker DC realizes our ad punk at the image
objects, it also DC realizes this image post. And at that moment doctrine has never
seen that this image posts object. It doesn't in that it's memory, it doesn't see
this as something that it queried for. That's why I originally, when we didn't have
the persist, when you call flush doctrine simply had no idea that this image posts
object existed. It did not query for it. Um, directly when we added the persist
doctor now Sarvis and it mistakenly thought that this was eight new object and it
inserted it into the database.

Okay.

This is a a bit of a hard thing to debug, but the reason I wanted to show you to you
is it actually highlights something that we did sort of incorrectly in the first
place. In general with your messages, you want to make them contain the minimum
amount of data possible. You want them to contain just the specific data they need to
do their job. It just makes them leaner and smaller and more directed. So really if
you think about our message, we don't really need the entire image posts object. The
the smallest thing that we need to pass is actually the ID smashing gonna change just
to int image post Id. Can you just do id, I'm going to refactor this to id, that's
going to rename my gitter. Change this to into down there. Perfect. It can get rid of
this EU statement and then I'm gonna go onto my image post controller, search for ad,
Ponca to image.

And here I'm actually past the idea that's the smallest unit that I need to make this
thing work then in our handler. But we just needed to do a little bit more work here.
So this is not an image post anymore. It's an image post Id. In order to query for
this, we're going to need the repository. So I'm the type hint image post repository
and I'll have alt enter initialize fields to create that property and set it. Then
finally down here we can say image post = this->image posts, repository, Arrow, find
image, post id, and that's it. So we passed that smallest thing possible and then we
actually go fetch it later and now we can truly remove this persist. That's, this is
going to be something that doctorate is going to know that a queried for just like
any normal situation. So when it saves it, it should see it. So once again, since we
just updated our code, we need to go over and restart our worker. Perfect. Now let's
go over here, upload a new file. I'll check on the worker. Yep. It process just fine.

Okay.

And when refresh. Yes, it works perfectly. So the best practice here is to pass the
smallest amount of information that you need. Um, and in this case we need this case.
That is the ID. When some cases you're not even going to need the ID. You're going to
in a few minutes, I'm actually gonna show you a different example, but sometimes you
don't even need to pass the ID. Sometimes you might just need to pass in a specific,
uh, string or file or something like that. We're actually going to see an example of
that in a few minutes but never passed the a entity object, uh, cause that's gonna
cause problems. Now one thing you might notice that is we do have an edge case here,
which is that what if the image post can't be found for this image post id? It's
possible that the image post was created and then deleted before our worker could
actually process it.

So what do you want to do here? Depends on your situation and how crazy it would be
if that image is didn't exist. So I'm gonna start with saying, if not image post that
we're going to want to do something because we don't want to just allow it to call a
get file name. On a knoll object down there. And when I'm gonna do is I'm going to
say, look, this is probably okay because it just means that the image is always
already been deleted. But I'm going to log a message just in case so that we know if
a, that maybe something is could be potentially wrong with us or the easy way and
Symfony 4.3 to get the logger is to go up to your service, make it implement a new
lager aware interface.

Yeah.

And then you can use a special trait called lager aware trait.

Yeah.

And as soon as you do that, I'll kind of open that logger where trait there Symfony
thinks the auto writing system was going to cost that lager and you have now have new
lager property. So now here to kind of summarize, we have two options we can do here.
And depending on, depending on your situation, we could throw an exception

which would cause the message to be retried. We're going to talk about failures and
retries and a second or you could return and this message will be discarded. It'll
basically look like this message was processed successfully and it won't be removed
from the queue. So when I'm going to do is actually return, but I'm also going to log
a message. I'm going to say if this->logger, then this air logger error alert, I'll
put a little message here that says image post percent d was missing. We'll pass the
image post id right there. Now the only reason I'm saying if this error logger, uh,
if you're, if you're using Symfony will call set lager and passing that logger. I'm
only doing that because if you want a unit test your handler, it's a little bit
easier to allow the longer to be no technically and an object oriented level than
lager couldn't be there, but it will be there. All right, so we can actually try this
out.

Okay,

so let's go over and let's stop her handler. And because our message is take a couple
of seconds to process, we can actually upload a bunch of these and then I will
immediately delete them. Let's see if we can get one of these

to get handled pretty quickly.

All right, so let's go check on the handle handler code and yeah, you can see it in
there. So some of these are process successfully, but you can actually see that this
one has an alert. You can also see one other interesting thing because of kind of a
race condition. There's also one down here that says an exception occurred while
handling the message file, not found out path. This was a situation where, uh, the
image post actually was still fond, the database by the time, but by the time it got
down here to read the message, they had been deleted from the filesystem. So you can
actually see, in that case, our handler through an exception. And you can see it
started retrying the message, we're going to talk about retries in a few minutes, but
it actually reattempted that message automatically.

Okay.