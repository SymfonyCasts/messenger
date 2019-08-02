# Functional Test for the Upload Endpoint

How can we write automated tests for all of this? Well... I have so many answers
for that. First, you could unit test your *message* classes. I don't *normally*
do this... because those classes *tend* to be so simple... but if your class is
a bit more complex or you want to play it safe, you can *totally* unit test this.

More important are the message handlers: it's *definitely* a good idea to test
these. You could write unit tests and mock the dependencies or write an integration
test... depending on what's most useful for what each handler does.

The point is: for message and message handler classes... testing them has absolutely
*nothing* to do with messenger or transports or async or workers: they're just
well-written PHP classes that we can test like *anything* else. That's really one
of the beautiful things about messenger: above all else, you're just writing nice
code.

But *functional* tests are more interesting. For example, open
`src/Controller/ImagePostController.php`. The `create()` method is the upload
endpoint and it does a couple of things: like saving the `ImagePost` to the database
and, most important for us, dispatching the `AddPonkaToImage` object.

Writing a functional test for this endpoint is actually fairly straightforward.
But what if we wanted to be able to test not *only* that this endpoint "appears"
to have worked, but also that the `AddPonkaToImage` object *was*, in fact, sent
to the transport? After all, we can't test that Ponka *was* actually added to the
image because, by the time the response is returned, it hasn't happened yet!

## Test Setup

Let's get the functional test working first, before we get all fancy. Start by
finding an open terminal and running:

```terminal
composer require phpunit --dev
```

That installs Symfony's `test-pack`, which includes the PHPUnit bridge - a sort
of "wrapper" around PHPUnit that makes life easier. When it finishes, it tells
us to write our tests inside the `tests/` directory - brilliant idea - and
execute them by running `php bin/phpunit`. That little file was just added by
the recipe and it handles all the details of getting PHPUnit running.

Ok, step one: create the test class. Inside `tests`, create a new `Controller/`
directory and then a new PHP Class: `ImagePostControllerTest`. Instead of making
this extend the normal `TestCase` from PHPUnit, extend `WebTestCase`, which will
give us the functional testing superpowers we deserve... and need. The class lives
in FrameworkBundle but... be careful because there are (gasp) *two* classes with
this name! The one you want lives in the `Test` namespace. The one you *don't* want
lives in the `Tests` namespace... so it's super confusing. It should look like this.
If you choose the wrong one, delete the `use` statement and try again.

[[[ code('e4e1fc7dd3') ]]]

*But*.... while writing this tutorial and getting mad about this confusing part,
I created an issue on the Symfony repository. And I'm *thrilled* that by the time
I recorded the audio, the other class has already been renamed! Thanks to
[janvt](https://github.com/janvt) who jumped on that. Go open source!

Anyways, because we're going to test the `create()` endpoint, add
`public function testCreate()`. Inside, to make sure things are working, I'll
try my favorite `$this->assertEquals(42, 42)`.

[[[ code('a29494d17b') ]]]

## Running the Test

Notice that I didn't get any auto-completion on this. That's because PHPUnit *itself*
hasn't been downloaded yet. Check it out: find your terminal and run the tests
with:

```terminal
php bin/phpunit
```

This little script uses Composer to download PHPUnit into a separate directory
in the background, which is nice because it means you can get any version of
PHPUnit, even if some of its dependencies clash with those in your project.

Once it's done... ding! Our one test is green. And the next time we run:

```terminal
php bin/phpunit
```

it jumps *straight* to the tests. And now that PHPUnit is downloaded, once PhpStorm builds its cache, that yellow background on `assertEquals()` will go away.

## Testing the Upload Endpoint

To test the endpoint itself, we *first* need an image that we can upload. Inside
the `tests/` directory, let's create a `fixtures/` directory to hold that image.
Now I'll copy one of the images I've been uploading into this directory and name
it `ryan-fabien.jpg`.

There it is. The test itself is pretty simple: create a client with
`$client = static::createClient()` and an `UploadedFile` object that will
represent the file being uploaded: `$uploadedFile = new UploadedFile()` passing
the path to the file as the first argument - `__DIR__.'/../fixtures/ryan-fabien.jpg` -
and the filename as the second - `ryan-fabien.jpg`.

[[[ code('5c7266be16') ]]]

Why the, sorta, "redundant" second argument? When you upload a file in a browser,
your browser sends *two* pieces of information: the physical contents of the file
*and* the name of the file on your filesystem.

Finally, we can make the request: `$client->request()`. The first argument is
the method... which is `POST`, then the URL - `/api/images` - we don't need any
GET or POST parameters, but we *do* need to pass an array of files.

[[[ code('51b446e8e8') ]]]

If you look in `ImagePostController`, we're expecting the name of the uploaded
file - that's normally the `name` attribute on the `<input` field - to literally
be `file`. Not the *most* creative name ever... but sensible. Use that key in our
test and set it to the `$uploadedFile` object.

[[[ code('a3f5d9abdd') ]]]

And... that's it! To see if it worked, let's just
`dd($client->getResponse()->getContent())`.

[[[ code('a3f5d9abdd') ]]]

Testing time! Find your terminal, clear the screen, deep breath and...

```terminal
php bin/phpunit
```

Got it! And we get a new id each time we run it. The `ImagePost` records are saving
to our *normal* database because I haven't gone to the trouble of creating a
separate database for my `test` environment. That *is* something I normally like
to do.

## Asserting Success

Remove the `dd()`: let's use a real assertion: `$this->assertResponseIsSuccessful()`.

[[[ code('276fcce839') ]]]

This nice method was added in Symfony 4.3... and it's not the only one: this new
`WebTestAssertionsTrait` has a *ton* of nice new methods for testing a whole
bunch of stuff!

If we stopped now... this is a nice test and you might be perfectly happy with
it. But... there's one part that's *not* ideal. Right now, when we run our test,
the `AddPonkaToImage` message is *actually* being sent to our transport... or
at least we *think* it is... we're not actually verifying that this happened...
though we can check manually right now.

To make this test more useful, we can do one of two different things. First, we
could override the transports to be synchronous in the test environment - just like
we did with `dev`. Then, if handling the message failed, our test would fail.

Or, second, we could *at least* write some code here that *proves* that the message
was *at least* sent to the transport. Right now, it's possible that the endpoint
could return 200... but some bug in our code caused the message never to be dispatched.

Let's add that check next, by leveraging a special "in memory" transport.
