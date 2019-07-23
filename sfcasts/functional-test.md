# Functional Test

Coming soon...

What about testing all this setup, writing actually unit or functional tests? How do
we do that? Well, the answer is a couple of pieces. Uh, first of all, you could write
a unit test for your individual message classes. I probably wouldn't normally do that
because they're so simple. But if you do like to be really tight with your tests,
this is a classic thing. You could unit tests. Um, probably more importantly is the
um, message handlers. These are something that are definitely a good idea to unit
test. You would of course use mocking to mock the service dependencies and then you
could dip patent, a unit test, whatever you wanted. You gave an integration test
these if you want it to really make sure that they're working properly. So basically
messages and message handlers. As far as testing those, they have nothing to do with
Messenger or transports or async. These are just PHP classes that are well written
and so we can unit test them like we unit test anything else,

a functional tests. Functional tests, however, are a little bit more interesting. For
example, if we go to `src/Controller/ImagePostController.php`, the `create()` method here
is actually the upload a end point and it does a couple of things. That of course
actually saves the image of the database and moves the file. And then most
importantly down here it actually dispatched, it dispatches our message, which we
know is going to be dispatched asynchronously to queue. So writing a functional test
for this endpoint is actually fairly straight forward. But what if you actually
wanted to be able to test that your message was sent to the transport, not just that
this endpoint worked, but that the `AddPonkaToImage` object was actually sent
properly. And we can actually do that in a really cool way. So let's go to this whole
process of setting up a nice functional tasks for that endpoint. Firstly, I want to
do is say, 

```terminal
composer require phpunit --dev
```

That's going to install the Symfony's phpunit, um, pack test-pack,

which includes `symfony/phpunit-bridge`, which is the way that we're going to
execute PHPunit and includes a couple of other things.

Perfect.

Now is it that it says down here we're going to write a test inside the `tests/` folder
and eventually we're gonna run `php bin/phpunit` to run our tests. That's a little
script that was just added by the recipe that will help bootstrap our tests, so let's
do that. Let's go down to the `tests/` directory here. Inside of here, I'm going to
create a new `Controller/` directory because we're

for functional tasks, I like to name them after my controller and it'll say 
`ImagePostsControllerTest`

and then instead of making this extend the normal `TestCase` for PHP unit a to get
some a nice functional testing tools, we're going to extend the `WebTestCase`. The
one from FrameworkBundle, and unfortunately there are actually two in framework
bundle right now. One of them is an internal that you're not supposed to use. The one
you actually want is the one in `Test/`, not to the one in `Tests` that has a very similar
name inside. It should look a little bit like this. Just make sure you've got the
right one. And then because we're going to be testing the create end point, I'll say
`testCreate()` and just to make sure things are working. I'll do my classic, 
`$this->assertEquals(42, 42)`. You won't get out of completion on this yet. Um, because because
technically speaking PHP unit, it hasn't been downloaded yet.

We're going to see that in a second and then we'll see that right now. Cause if you
flip over, I'll clear my screen or run 

```terminal
php bin/phpunit
```

This is a little script
that will actually download phpunit into a separate directory behind the scenes. Um,
it's outside the scope of this tutorial but Symfony basically does that. So you can
have a version of PHP unit, um, and its dependencies that don't clash with the
dependencies of your project. So you can sort of run piece unit in isolation and then
it runs a test one test, one assertion. And if you're wanting again, peace of mind,
it's already downloaded. 

```terminal-silent
php bin/phpunit
```

So it just works. And now on a foot back after it builds.
And we are going to get a, you're going to see that yellow background on `assertEquals()` is
gonna go away because now PhpStorm sees our assert functions. Okay, so let's actually
get this to testing the upload endpoint. A first I'm going to, I'm going to need an
image to upload and inside my `tests/` directory I'm actually going to create a `fixtures/`
directory.

And then at the command line I'm going to move one of my files. I've been uploading
to that directory. So I'm gonna go to my needs more cat. And this is the one that I'm
going to want. This is me and Favian and I'll move that into 
`tests/fixtures/ryan-fabien.jpg`  Perfect. So now instead of there, cool. So now
we have an image we can actually play with. Okay. D for the test itself first and we
need to do is say `$client = static::client`. Okay. So I'll click on `createClient()`
that will create the, basically the http client that is going to make requests
into our application. And then I'm going to create an `$uploadedFile` variable set to
`new UploadedFile()`. You'll see how we're going to use this in a second. Make sure you
get the one from HttpFoundation and this takes two arguments.

The first argument is the actual path to the file itself, so I can say
`__DIR__.'/../fixtures/ryan-fabien.jpg'` and the
second argument, the only other required argument is the original name. I'm going to
say it, `ryan-fabien.jpg`. This is because when you upload a file on a
browser, the final actually sends the physical contents of the file in your browser
is also responsible for actually sending the file name on the user's filesystem. This
could be anything, but we'll keep it consistent.

and then to actually make the request, we'll say a `$client->request()` and this is
of course going to be a `POST` your request. So `$client->request('POST')`,

then the URL which is `/api/images`. And then we don't need any special parameters. We
don't, but we do need to pass a files array here and array of uploaded files. Now if
you look in `ImagePostController`, we're actually expecting the a, the kind of a name
on the input field here to be literally the string of `file`. So that's the key that
we're gonna use here when it's that `'file' => $uploadedFile` object and that's
it. That should make the request and see if it worked. I'm going to use `dd()` down here
and I'm going to say `$client->getResponse()->getContent()`
So once your client makes a request, you can actually get the response
by saying client Eric, your response. So ideally this should give us our nice JSON
Return of that uploaded file.

Let's try it. Move over. I'll clear the screen.

```terminal
php bin/phpunit
```

and it works perfect. You can see the ID 102. Let me try that a couple more times.
I do. You want a three? It's adding things to the database.

Is that any of them to our normal database right now? I haven't gone to the trouble
of creating a separate database for my test environment, which I usually do, but
that's fine. All right, so I'll remove that `dd()` here I am gonna put an assertion here.
I'm going to say `$this->assertResponseIsSuccessful()`. Uh, this is actually, um, a new
method in Symfony 4.3, uh, where a bunch of really nice, uh, assertions were added by
Symfony related to testing. So actually if I hold command and get into this, this
opens up something called a web at test assertions trait and needs to, there's lots
of good stuff. You're going to start the status coding a certain things redirected,
you can check for headers. Um, so lots of really, really good stuff in here that,
that um, you should check out,

um,

to help make testing easier.

So if we just stopped right now, so actually, so this is actually a really nice test.
However, there's a couple of problems. First of all, behind the scenes, we really are
still dispatching our `AddPonkaToImage` to our transport. So it's literally being
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