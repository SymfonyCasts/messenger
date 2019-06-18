# Install

Coming soon...

Hi Friends. Welcome to our tutorial on Messenger.

Which is a topic that's near and dear to my heart because a, it's just a super fun
thing to work with. And B, because I was one of many people that added a bunch of
really cool features for Symfony 4.3 that makes Messenger really, really shine. So
I'm super excited to show you those. Now Messenger itself is just a component inside
of Symfony. It's just kind of a small tool that can really change your workflow and
it's just an absolute joy to work with. It enables a design pattern of messages and
handlers which are going to see shortly. And that design pattern makes it possible to
do work asynchronously via queues, which is ultimately what we're going to talk about.

So as always, to get the most of this tutorial, you should totally code along with
me. Download the course code on this page.


And when you unzip it, you'll have a start directory in a start directory with the
same code that you see here. You can open up this. `README.MD` file. Follow the
setup instructions to get your project rocking. The last step will be to find a
terminal and use the symfony console tool to run

```terminal
symfony serve
```

We'll start a built in web server at `localhost:8000` let's go check this out.

Say hello to our very important APP called Ponka-fy Me. If you don't know, Ponka is
Victor's cat who is works on our team. I love being able to work on the
wording later. Victor works on our team. Oh yeah. Ponka is a team member here and
we've been having the problem where we often go on vacation, Ponka can't come more
with and then when we come back, none of our photos have Ponka in them, so we thought
let's make a site to do that. So we have a little upload widget here. I'll select a
vacation photo with me, Leanna and her brother. It uploads over here and boom over
here. Check this out. We get a beautiful photo with Ponka in it.

Behind the scenes, this actually uses eight. This is a Vue js frontend, which is
not that important. The important thing to understand is that this uploads a to an
API end point. And in that API end point, we store the uploaded file and then we need
to do some heavy work. We need to do heavy work of actually manipulating these two
images and then sending them back, which is the reason why when we do this,

it's not that fast. You can say it finishes uploading here, you weigh in a second or
two and then it pops up over here. It's a little bit slow because it's taken some
time to process their controller behind this is in `src/Controller/ImagePostController.php`
And here's the endpoint. That's really important to `create()` endpoint,
grabs the file, validates it, uh, ultimately uses another service to store that file.
And then down here it uses another service that actually, um, adds the pumpkin image.
So this is kind of the area here that's a little bit of like heavy work that's
happening. Kind of looked at this `$photoManager->update()`.


Or not that if you look at this, `ponkafy()` method here, there's some heavy 
kind of a image manipulation stuff going and for dramatic effect. 
I even had a little `sleep(2)` there to make it seem extra slow.

Okay. So this is just great boring code and we're going to see how Messenger is going
to really change the way that this code looks and works. So before we actually start
getting into it, we need to get it installed. So I'll go to my terminal, open a new
tab and run 

```terminal
composer require messenger
```

Well that finishes, you can see some information here that's actually coming from 
the recipe and we're going to talk about all what all this stuff is as we go along. 
This really isn't, it made two changes to our application beyond the normal stuff. 
It modified `.env`

and add a new messenger. Transport. Transport's going to be something we talk about
when we start, um, using queuing systems to cues some of our work. And the only thing
it did is it added a new `messenger.yaml` file, which I just want to show you, contains
absolutely nothing interesting right now. So this transports routing stuff, we'll
talk about that, but you can see, you can see it's all completely empty. So at this
point, the only thing that installing messenger gave us is it gives us a new service.
So let's run 

```terminal
php bin/console debug:autowiring mess
```

I'll search search for a `mess`, and you can see that we have a new `MessageBusInterface` 
class, which we're going to learn. So next we're going to learn about Message classes, 
Message handlers, and how the Message Bus, how we can use a message bus to do that work.
