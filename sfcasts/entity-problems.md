# Problems with Entities in Messages

We've got a strange issue: we know that `AddPonkaToImageHandler` is being
called successfully by the worker process.... because it's *actually* adding
Ponka to the images! But, for some reason... even though we call
`$imagePost->markAsPonkaAdded()`... which sets the `$ponkaAddedAt` property...
and then `$this->entityManager->flush()`... it doesn't seem to be saving!

## Maybe we're Missing persist()?

So.. you might wonder:

> Do I need to call persist() on `$imagePost`?

Let's try it: `$this->entityManager->persist($imagePost)`. In theory, we should
*not* need this: you only need to call `persist()` on *new* objects that you want
to save. It's not needed... and normally does *nothing*... when you call it
on an object that will be updated.

But... what the heck... let's see what happens.

## Restarting the Worker

Oh! But before we try this... we need to do something *very* important! Find your
terminal, press Ctrl+C to stop the worker, then restart it:

```terminal
php bin/console messenger:consume
```

Why? As you know, workers sit there and run... forever. The *problem* is that,
if you update some of your code, the worker won't see it! Until you restart it,
it still has the *old* code stored in memory! So anytime you make a change to
code that a worker uses, be sure to restart it. Later, we'll talk about how to
do this safely when you deploy.

## The Weirdness of Serialized Entities

Let's see what happens now that we've added that new `persist()` call. Upload
one new file, find your worker and... yep! It was handled successfully. Did that
fix the entity saving problem? Refresh the page.

Yikes! What just happened! The image shows up *twice*! One *with* the date set...
and one without. To the database!

```terminal
SELECT * FROM image_post \G
```

Yea... this one image is on *two* rows: I know because they're pointing to the
*exact* same file on the filesystem. The worker... somehow... *duplicated* that
row in the database.

## Doctrine's Identity Map

This... is a confusing bug... but it has an easy fix. First, let's look at things
from Doctrine's perspective. Internally, Doctrine keeps track of a list of all
the entity objects that it's currently dealing with. When you query for an entity,
it adds it to this list. When you call `persist()`, if it's not already in the list,
it's added. *Then*, when we call `flush()`, Doctrine loops over *all* of these
objects, looks for any that changed, and creates the appropriate UPDATE or INSERT
queries. It knows whether or not an object should be inserted or updated because
it knows whether or not *it* was responsible for *querying* for that object.
By the way, if you want to nerd out on this topic more, this "list" is called
the identity map... and it's just a big array that starts empty at the beginning
of each request and gets bigger as you query or persist things.

So now let's think about what happens in our worker. When it deserializes
the `AddPonkaToImage` object, it *also* deserializes the `ImagePost` object that
lives inside. At *that* moment, Doctrine's identity map does *not* contain this
object... because it did not query for it inside this PHP process - from inside
the worker. That's why originally, before we added `persist()`, when we called
`flush()`, Doctrine looked at the list of objects in its identity map - which
was *empty* - and... did absolutely nothing: it doesn't know it's supposed to save
the `ImagePost`!

When we added `persist()`, we created a different issue. Doctrine *is* now aware
that it needs to save this... but because it didn't original query for it, it
mistakenly thinks that this should be *inserted* into the database as a *new*
row, instead of updating.

Phew! I wanted you to see this because... it *is* kinda hard to debug. Fortunately,
the fix is easy. *And* it touches on an important best-practice for your messages:
include *only* the information you need. That's next.
