# Worker Command

Even if I refresh the page, now that our messages aren't being handled immediately...
the four most recent photos don't have Ponka in them. That's tragic! Instead, those
messages were sent to the `doctrine` transport and are waiting patiently inside of
a `messenger_messages` table.

So... how can we read these back out and process them? We need something that can
fetch each row one-by-one, *deserialize* each message back into PHP, then pass it
to the message bus to be *actually* handled. That "thing" is a special console
command. Run:

```terminal
php bin/console messenger:consume
```

You won't see any output... yet... but, unless we messed something up, this is
doing exactly what we need: reading each message, deserializing it, and sending it
*back* to the bus for handling.

So... let's go refresh. Woh! It *did* work! All 4 messages now have Ponka on them!
We're saved!

## messenger:consume -vv

To make this more interesting, as you can see, it says to run this command with
`-vv` if you want to see what it's doing behind-the-scenes. But... interesting,
once the command finished reading and handling all 4 messages... it didn't quit:
it's *still* running. And if we restart it with `-vv` on the end:

```terminal-silent
php bin/console messenger:consume -vv
```

... it does the same. A command that "handles" messages from a queue is called a
"worker". And the job of a worker is to watch and wait for new messages to be added
to the queue and handle them the *instant* one is added. It waits and runs... forever!
Well, that's not *totally* true - but more on that later when we talk about deployment.

Let's peek back over in our "queue" - the `messenger_messages` table:

```terminal-silent
SELECT * FROM messenger_messages \G
```

Yep! This holds *zero* rows because all those messages were processed and removed
from the queue. Back at the browser, let's upload... how about... 5 new photos. Woh...
that was *awesome* fast!

Ok, ok, move back to the terminal that's running the worker! We can see it doing
its job! It says: "Received message", "Message handled by `AddPonkaToImageHandler`"
then "`AddPonkaToImage` was handled successfully (acknowledging)". That last part,
"acknowledging" means that Messenger notified the Doctrine transport that the message
was handled and can be removed from the queue.

Then... it keeps going to the next message... and the next... and the next...
until it's done. So if we refresh... Ponka was added to all of these! Let's do
it again - upload 5 more photos. And... let's refresh and watch... there's Ponka!
We can see them being handled little-by-little. So much wonderful Ponka!

Ok, this *would* be cooler if our JavaScript automatically refreshed the image
when Ponka was added... instead of me needing to refresh the page... but that's
a *totally* different topic, and one that's covered by the Mercure component in
Symfony.

And... that's it! This `messenger:consume` command is something that you'll have
running on production *all* the time. Heck, you might decide to run *multiple*
worker processes. *Or*, you could *even* deploy your app to a totally *different*
server - one that's not handling web requests - and run the worker processes there!
*Then*, handling these messages wouldn't use *any* resources from your web server.
We'll talk more about deployment later.

## Problem: Database Didn't Update?

Because right now... we have a problem... a kinda weird problem. Refresh the page.
Hmm, the original photos all say something like:

> Ponka visited 13 minutes ago. Ponka visited 11 minutes ago.

But, since we made things asynchronous, these all say:

> Ponka is napping. Check back soon.

Open up the `ImagePost` entity and find the `$ponkaAddedAt` property. This is a
`datetime` field, which records *when* Ponka was added to the photo. The message
on the front-end comes from this value.

For the original ones... back when the whole process was synchronous, this field
*was* set successfully. But now... it looks like it isn't. Let's check the database
to be sure. Over in MySQL, run:

```terminal
SELECT * FROM image_post \G
```

All the way back in the beginning... `ponka_added_at` *was* being set. But now
they're all `null`. So... our images are being processed correctly, but, for
some reason, this field in the database is *not*. If we look inside
`AddPonkaToImageHandler`... yea... right here: `$imagePost->markPonkaAsAdded()`.
*That* sets the property. So... why isn't it saving?

Let's figure out what's going on next and learn a bit more about some "best practices"
when it comes to building your message class.
