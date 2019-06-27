# Installing Messenger

Yo Friends! It's Symfony Messenger time!!! So, what *is* Symfony Messenger? It's
a tool that allows you to... um... send messages... Wait... that made no sense.

## Um, What *is* Messenger?

Let's try again. Messenger is a tool that enables a *really* cool design pattern
where you write "messages" and then other code that *does* something when that
message is sent. If you've heard of CQRS - Command Query Responsibility Segregation -
Messenger is a tool that enables that design pattern.

That's all great... and we're going to learn *plenty* about it. But there's a good
chance you're watching this because you want to learn about something *else*
that Messenger does: it allows you to run code asynchronously with queues & workers!
OooooOOoo. That's the *real* fanciness of Messenger.

Oh, and I have two more sales pitches. First, Symfony 4.3 has a *ton* of new features
that *really* make Messenger shine. And second, using Messenger is an absolute
delight. So... let's do this!

## Project Setup

If you want to become a command-bus-queue-processing-worker-middleware-envelope...
and other buzzwords... Messenger *master*, warm up your coffee and code along with
me. Download the course code from this page. When you unzip it, you'll find a
`start/` directory inside with the same code that you see here. Open up the
`README.md` file for *all* the details about how to get the project running *and*
a totally-unrelated, yet, lovely poem called "The Messenger".

The last setup step will be to find a terminal and use the Symfony binary to start
a web-server at `https://localhost:8000`:

```terminal-silent
symfony serve
```

Ok, let's go check that out in our browser. Say hello to our newest
SymfonyCasts creation: Ponka-fy Me. If you didn't already know, Ponka, by day, is
one of the lead developers here at SymfonyCasts. By night... she is Victor's
cat. Actually... due to her frequent nap schedule... she doesn't really *do* any
coding... now that I think about it.

## Ponka-fy Me

Anyways, we've been noticing a problem where we go on vacation, but Ponka can't
come... so when we return, none of our photos have Ponka in them! Ponka-fy Me
solves that: let's select a vacation photo... it uploads... and... yea! Check it
out! Ponka *seamlessly* joined us in our vacation photo!

Behind the scenes, this app uses a Vue.js frontend... which isn't important
for what we'll be learning. What *is* important to know is that this uploads to
an API endpoint which stores the photo and then *combines* two images together.
That's a pretty heavy thing to do on a web request... which is why, if you watch
closely, it's kinda slow: it will finish uploading... wait... and, yep, *then*
load the new image on the right.

Let's look at the API endpoint so you can get an idea of how this works: it lives
at `src/Controller/ImagePostController.php`. Look for `create()` *this* is the
upload API endpoint: it grabs the file, validates it, uses *another* service
to store that file - that's the `uploadImage()` method, creates a new `ImagePost`
entity, saves it to the database with Doctrine and *then*, down here, we have
some code to add Ponka to our photo. That `ponkafy()` method does the *really*
heavy-lifting: it takes the two images, splices them together and... to make it
extra dramatic and slow-looking for the purposes of this tutorial, it takes a 2
second break for tea.

Mostly... all of this code is meant to be *pretty* boring. Sure, I've organized
things into a few services... that's nice - but it's all very traditional. It's
a *perfect* test case for Messenger!

## Installing Messenger

So... let's get it installed! Find your terminal, open a new tab and run:

```terminal
composer require messenger
```

When that finishes... we get a "message"... from Messenger! Well, from its recipe.
This is great - but we'll talk about all this stuff along the way.

In addition to installing the Messenger component, its Flex recipe made two changes
to our app. First, it modified `.env`. Let's see... it added this "transport"
config. This relates to queuing messages - a lot more on that later. 

[[[ code('932e6f69b2') ]]]

It also added a new `messenger.yaml` file, which... if you open that up... 
is *perfectly*... boring! It has `transports` and `routing` keys - again, 
things that relate to queuing - but it's all empty and doesn't do *anything* yet.

[[[ code('8aa3fd7bc5') ]]]

So... what *did* installing the Messenger component give us... other than some
new PHP classes inside the `vendor/` directory? It gave us one new important
service. Back at your terminal run:

```terminal
php bin/console debug:autowiring mess
```

There it is! We have a new service that we can use with this `MessageBusInterface`
type-hint. Um... what does it do? I don't know! But let's find out next! Along
with learning about message classes and message handlers.
