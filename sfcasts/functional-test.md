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
confusing. If you choose the wrong one, delete the `use` statement and try again.
Good news - there's an open pull request to rename the "wrong" class to make this
all *much* more awesome.

--> HERE

and then instead of making this extend the normal test case for PHP unit a to get
some a nice functional testing tools, we're going to extend the web test case. The
one from framework bundle, and unfortunately there are actually two in framework
bundle right now. One of them is an internal that you're not supposed to use. The one
you actually want is the one in test, not to the one in tests that has a very similar
name inside. It should look a little bit like this. Just make sure you've got the
right one. And then because we're going to be testing the create end point, I'll say
test, create and just to make sure things are working. I'll do my classic, this
assert equals 40 to 42. You won't get out of completion on this yet. Um, because
because technically speaking PHP unit, it hasn't been downloaded yet.

We're going to see that in a second and then we'll see that right now. Cause if you
flip over, I'll clear my screen or run PHP bin Slash P PHB unit. This is a little
script that will actually download PHV unit into a separate directory behind the
scenes. Um, it's outside the scope of this tutorial but symphony basically does that.
So you can have a version of PHP unit, um, and its dependencies that don't clash with
the dependencies of your project. So you can sort of run piece unit in isolation and
then it runs a test one test, one assertion. And if you're wanting again, peace of
mind, it's already downloaded. So it just works. And now on a foot back after it
builds. And we are going to get a, you're going to see that yellow background on
assert equals is gonna go away because now petri storm sees our assert functions.
Okay, so let's actually get this to testing the upload endpoint. A first I'm going
to, I'm going to need an image to upload and inside my test directory I'm actually
going to create a fixtures directory.

And then at the command line I'm going to move one of my files. I've been uploading
to that directory. So I'm gonna go to my needs more cat. And this is the one that I'm
going to want. This is me and Favian and I'll move that into fixtures, tests,
fixtures, Ryan Dash Favian, that jpeg. Perfect. So now instead of there, cool. So now
we have an image we can actually play with. Okay. D for the test itself first and we
need to do is say client equals static colon. Colon client. Okay. So I'll click on
create client that will create the, basically the http client that is going to make
requests into our application. And then I'm going to create an uploaded file variable
set to new uploaded file. You'll see how we're going to use this in a second. Make
sure you get the one from http foundation and this takes two arguments.

The first argument is the actual path to the file itself, so I can say underscore,
underscore Dir underscore Ernest score that that slash fixtures slash Ryan Best five
minute jpeg and the second argument, the only other required argument is the original
name. I'm going to say it, Ryan Dash five in that jpeg. This is because when you
upload a file on a browser, the final actually sends the physical contents of the
file in your browser is also responsible for actually sending the file name on the
user's file system. This could be anything, but we'll keep it consistent.

Okay,

and then to actually make the request, we'll say a client error request and this is
of course going to be a

post your request. So client area requests post,

then the URL which is slash API slash images. And then we don't need any special
parameters. We don't, but we do need to pass a files array here and array of uploaded
files. Now if you look in image post controller, we're actually expecting the a, the
kind of a name on the input field here to be literally the string of file. So that's
the key that we're gonna use here when it's that file set to that uploaded file
object and that's it. That should make the request and see if it worked. I'm going to
use d d down here and I'm going to say client Arrow.

Okay. Get Response Arrow.

Get content. So once your client makes a request, you can actually get the response
by saying client Eric, your response. So ideally this should give us our nice Jason
Return of that uploaded file.

Let's try it. Move over. I'll clear the screen. Okay.

Ron Phd been slash unit

and

it works perfect. You can see the ID one to two. Let me try that a couple more times.
I do. You want a three? It's adding things to the database.

Okay.

Is that any of them to our normal database right now? I haven't gone to the trouble
of creating a separate database for my test environment, which I usually do, but
that's fine. All right, so I'll remove that d here I am gonna put an assertion here.
I'm going to say this assert response is successful. Uh, this is actually, um, a new
method in symphony 4.3, uh, where a bunch of really nice, uh, assertions were added
by symphony related to testing. So actually if I hold command and get into this, this
opens up something called a web at test assertions trait and needs to, there's lots
of good stuff. You're going to start the status coding a certain things redirected,
you can check for headers. Um, so lots of really, really good stuff in here that,
that um, you should check out,

um,

to help make testing easier.

So if we just stopped right now, so actually, so this is actually a really nice test.
However, there's a couple of problems. First of all, behind the scenes, we really are
still dispatching our ad Ponca to image to our transport. So it's literally being
added to our database right now. Select Star from Messenger underscore messages.
Slash g a is actually 40 rows in there cause there's lots of things from the failed
transport. So let's actually say to head where you name does not equal fail. Problem
number one is that our messages are being said the transport, and you can actually
see this. If you go back to our worker tab here, this is actually, his messages are
from us in our test by run, Vinny run our tests again, you can actually see it's
actually processing those right here. Um, that's fine, but that's great that that's
working. But actually it doesn't allow us to do an assertion. What you might want to
do is actually be able to assert inside of your test that the message was actually
delivered to the transport. There's no way for us to tell right here that that's
actually happening successfully. So next what we're going to do is, is use something
called an in memory transport, do actually short circuit that system and be able to
assert literally in our tests that the message was in fact delivered to the transport
successfully.
