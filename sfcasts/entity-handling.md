# Passing Entity Ids inside of Messages

Suppose you need your friend to come over and watch your dog - let's call her Molly -
for the weekend. So you write them a message explaining all the details they need to
know: how often to feed Molly, when to walk her, exactly where she likes to be
scratched behind the ears, your favorite superhero and the name of your best friend
when you were little. Wait... those last two things... while *interesting*...
have *nothing* to do with watching Molly the dog!

And this touches on a best-practice for designing your message classes: make them
contain *all* the details the handler needs... and *nothing* extra. This isn't
an absolute rule... it just makes them leaner, smaller and more directed.

## Passing the Entity Id

If you think about our message, we don't *really* need the entire `ImagePost`
object. The *smallest* thing that we could pass is actually the id... which we
could then use to query for the `ImagePost` object and get the filename.

Change the constructor argument to `int $imagePostId`. I'll change that below
and go to Code -> Refactor to rename the property. Oh, and brilliant! It also
renamed my getter to `getImagePostId()`. Update the return type to be an `int`.
We can remove the old `use` statement as extra credit.

Next, in `ImagePostController`, search for  `AddPonkaToImage` and... change this
to `$imagePost->getId()`.

Our message class is now as *small* as it can get. Of course, this means that
we have a *little* bit extra work to do in our handler. First, the `$imagePost`
variable is not... well.. and `ImagePost` anymore! Rename it to `$imagePostId`.
To query for the actual object, add a new argument:
`ImagePostRepository $imagePostRepository`. I'll hit Alt + Enter -> Initialize Fields
to create that property and set it.

Finally, back in the method, we can say
`$imagePost = $this->imagePostRepository->find($imagePostId)`.

That's it! And this fixes our Doctrine problem! Now that we're querying for the
entity, when we call `flush()`, it will correctly save it. We can remove the
`persist()` because it's not needed when updating objects.

Let's try it! Because we just changed code in our handler, hit Ctrl+C to stop
our worker and then restart it:

```terminal-silent
php bin/console messenger:consume -vv
```

Here we go! Upload a new file... check the worker - yep, it processed just fine -
and... refresh! Yes! *No* duplication, Ponka is visiting my workshop and the
date is set!

## Failing Gracefully

But... sorry to bring up bad news... what if the `ImagePost` can't be found for
this `$imagePostId`? That *shouldn't* happen... but depending on your app, it
might be possible! For us... it is! If a user uploads a photo, then *deletes*
it before the worker can handle it, the `ImagePost` will be gone!

But... is that really a problem? If the `ImagePost` was already deleted, do we care
if this handler blows up? Probably not... as long as you've thought about *how*
it will explode and are intentional.

Check this out: let's start by saying: `if (!$imagePost)` so we can do some special
handling... instead of trying to call `getFilename()` on null down here. If this
happens, we know that it's *probably* just because the image was already deleted.
But... because I *hate* surprises in my code, let's log a message so that we know
this happened... *just* in case it's caused by a bug in our code.

## Logger Injection with LoggerAwareInterface

Starting in Symfony 4.2, there's a little shortcut to getting the main `logger`
service. First, make your service implement `LoggerAwareInterface`. Then, use
a trait called `LoggerAwareTrait`.

That's it! Let's peek inside `LoggerAwareTrait`. Ah, ok. In the core of Symfony,
there's a *little* bit of code that says:

> whenever you see a user's service that implements `LoggerAwareInterface`,
> automatically call `setLogger()` on it and pass the logger.

By combining the interface with this trait... we don't have to do anything! We
instantly have a `$logger` property we can use.

## How to Fail in your Handler

Ok, so back inside our if statement... what should we do if the `ImagePost` isn't
found? We have two options... and the correct choice depends on the situation.
First, we could throw an exception - any exception - and that would cause this
message to be retried. More on that soon. Or, you could simply "return" and this
message will "appear" to have been handled successfully... and it will be removed
from the queue.

Let's return: there's no point in retrying this message later... that `ImagePost`
is *gone*! But let's also log a message: if `$this->logger`, then
`$this->logger->alert()` with, how about,

> Image post %d was missing!

passing `$imagePostId` for the wildcard. Oh, and the only reason I'm checking to
see *if* `$this->logger` is set is... basically... to help with unit testing.
Inside Symfony, the `logger` property *will* always be set. But an on object-oriented
level, there's nothing that *guarantees* that someone will have called `setLogger()`...
so this is just a bit more responsible.

Ok, let's try this! Yea... let's see what happens if we delete an `ImagePost`
before it's processed! First, move over, stop the handler, and restart it:

```terminal-silent
php bin/console messenger:consume -vv
```

And because each message takes a few seconds to process, if we upload a *bunch*
of photos... and delete them as quick as we can... with any luck, we'll delete
one before its message is handled.

Let's see if it worked! So... some *did* process successfully. But... yea! This
one has an alert! And thanks to the "return" we added, it "looks" like it was
successfully handled.

Oh... and interesting... there's another error I didn't plan for below:

> An exception occurred while handling message AddPonkaToImage: File not
> found at path...

That's awesome! *This* is what it looks like if, for *any* reason, an *exception*
is thrown in your handler. Apparently the `ImagePost` *was* found in the database...
but by the time it tried to read the file on the filesystem, it had been deleted!

The *really* amazing part is that Messenger saw this failure and *automatically*
retried the message a second... then a third time. We'll talk more about failures
and retries a bit later.

But first, our `DeleteImagePost` message *is* still being handled synchronously.
Could we make it async? Well... no! We need the `ImagePost` to be deleted from
the database immediately so that the user stops seeing it. Unless... we could
split the delete task into two pieces... Let's try that next!
