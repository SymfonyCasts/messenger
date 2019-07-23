# Functional Test for the Upload Endpoint

How can we write automated tests for all of this? Well, the answer is a couple
of pieces. First, you could write a unit test for your message classes. I don't
*normally* do this... because they're so simple. But if your class is a bit more
complex or you want to play it safe, you can *totally* unit test this.

More important are the message handlers: it's *definitely* a good idea to test
these. You could write unit tests and mock the dependencies or an integration
test... depending on what's most useful for what each handler does.

The point is: for your message and message handler classes... testing them has
*nothing* to do with messenger or transports or async or workers: they're just
well-written PHP classes that we can test like *anything* else. That's really one
of the beautiful things about messenger: first and foremost, you're writing nice
code.

But *functional* tests can be a bit more interesting. For example, open
`src/Controller/ImagePostController.php`. The `create()` method is the upload
endpoint and it does a couple of things: like saving `ImagePost` to the database
and, most important for us, dispatching the `AddPonkaToImage` object.

Writing a functional test for this endpoint is actually fairly straightforward.
But what if we wanted to be able to test not *only* that this endpoint "appears"
to have worked, but also that the `AddPonkaToImage` object *was* sent to the
transport? Doing *that* is a bit more interesting.

## Test Setup

Let's get the functional test working first. Start by finding and open terminal
and running:

```terminal
composer require phpunit --dev
```

That will install Symfony `test-pack`, which includes the PHPUnit bridge - a sort
of "wrapper" around PHPUnit that makes life easier. When it finishes, it tells
us to write our tests inside of the `tests/` directory - brilliant idea - and
execute them by running `php bin/phpunit`. That little file was just added by
the recipe and it'll handle all the details of getting PHPUnit running.

Step one: create the test class: inside `tests`, create a new `Controller/` directory
and then a new PHP Class: `ImagePostControllerTest`. Instead of making this extend
the normal `TestCase` from PHPUnit, extend `WebTestCase`, which will give the
functional test superpowers we need. The class lives in FrameworkBundle but...
be careful: there are (gasp) *two* classes with this name! The one you want lives
in the `Test` namespace... the other one lives in `Tests/Test`... so it's super
confusing. It should look like this. If you choose the wrong one, delete the
`use` statement and try again. Good news - there's an open pull request to rename
the "wrong" class to make this all *much* more awesome.

And then, because we're going to test the `create()` endpoint, create
`public function testCreate()`. Inside, just to make sure things are working, I'll
try my favorite `$this->assertEquals(42, 42)`.

## Running the Test

Notice I didn't get any auto-completion on this. That's because PHPUnit *itself*
hasn't been downloaded yet. Check it out: find your terminal and run the tests
with:

```terminal
php bin/phpunit
```

This little script uses Composer to download PHPUnit into a separate directory
behind the scenes, which is nice because it means you can get any version of
PHPUnit, even if some of its dependency versions clash with those in your project.

Once it's done... ding! Our one test is green. Next time you run:

```terminal
php bin/phpunit
```

it jumps *straight* to running the tests. And now that PHPUnit is downloaded,
once PhpStorm builds its cache, that yellow background on `assertEquals()` will
go away.

## Testing the Upload Endpoint

To test the endpoint itself, we *first* need an image that we can upload to it.
Inside the `tests/` directory, let's create a `fixtures/` directory to hold the
image. Next, I'll copy one of the images I've been uploading into this directory
and name it `ryan-fabien.jpg`.

And... there it is. Now, the test is pretty simple: create a client with
`$client = static::createClient()` and an `UploadedFile` object that will
simulate the file being uploaded: `$uploadedFile = new UploadedFile()` passing
the path to the file as the first argument - `__DIR__.'/../fixtures/ryan-fabien.jpg` -
and the filename as the second - `ryan-fabien.jpg`.

Why the, sorta, "redundant" second argument? When you upload a file in a browser,
your browser sends *two* pieces of data: the physical contents of the file *and*
the name of the file on your filesystem.

Finally, we can make the request: `$client->request()`. The first argument is
the method... which is `POST`, then the URL - `/api/images` - we don't need any
GET or POST parameters, but we *do* need to pass an array of files.

If you look in `ImagePostController`, we're expecting the name of the uploaded
file - that's normally the `name` attribute on the `<input` field - to literally
be `file`. Use that key in our test and set it to our `$uploadedFile` object.

And... that's it! To see if it worked *really* easily, let's
`dd($client->getResponse()->getContent()`.

Let's try it! I'll find my terminal, clear the screen and run:

```terminal
php bin/phpunit
```

Got it! And we get a new id each time we run it. Right now this is saving to our
*normal* database because I haven't gone to the trouble of creating a separate
database for my test environment, which I usually like to do. But that's fine.

## Asserting Success

Remove the `dd()`: let's replace this with a real assertion:
`$this->assertResponseIsSuccessful()`.

This is a new method in Symfony 4.3... and it's not the only one: the new
`WebTestAssertionsTrait` has a *ton* of nice new methods for testing a variety
of things.

---> HERE

um,

to help make testing easier.

So if we just stopped right now, so actually, so this is actually a really nice test.
However, there's a couple of problems. First of all, behind the scenes, we really are
still dispatching our ad Ponca to image to our transport. So it's literally being
added to our database right now. Select Star from Messenger_messages. /g a is
actually 40 rows in there cause there's lots of things from the failed transport. So
let's actually say to head where you name does not equal fail. Problem number one is
that our messages are being said the transport, and you can actually see this. If you
go back to our worker tab here, this is actually, his messages are from us in our
test by run, Vinny run our tests again, you can actually see it's actually processing
those right here. Um, that's fine, but that's great that that's working. But actually
it doesn't allow us to do an assertion. What you might want to do is actually be able
to assert inside of your test that the message was actually delivered to the
transport. There's no way for us to tell right here that that's actually happening
successfully. So next what we're going to do is, is use something called an in memory
transport, do actually short circuit that system and be able to assert literally in
our tests that the message was in fact delivered to the transport successfully.
