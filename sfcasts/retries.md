# Retries

Coming soon...

When you start thinking about handling things asynchronously, thinking about handling
failures is key. Usually when you handle things synchronously, when something fails,
you typically want the whole process to fail, not just half of it. Like for example,
you wouldn't want to maybe save an `ImagePost` to the database but then have the
thumbnailing process down here, not work for some reason and this is something you
always need to think about even with synchronous coding and oftentimes if it's really
important to you to have everything work, you might wrap things in database
transactions. For example, you might wrap all of this in a database transaction so
that you are absolutely sure that the uh, Ponka images added in order for the image
post to actually be saved to the database. If this were all done in synchronous
coding with Async Coding, it's much easier for things to halfway fail.

Like for example, we can save this `ImagePost` to the database and then we can send
our `AddPonkaToImage` to our queue and then when we process that queue, that
could explode for some reason. And it's especially about if that failure is temporary
because what we don't want to have happen, because if this were all synchronous in it
and it failed, the user would see in air, but we would still be in a good initial
state and the user might just try it again and then the whole thing would be
successful. But if adding Ponka to image fails, then we have a situation where and
that message is just discarded. Then we have a situation where forever this 
`ImagePost` will be without a Ponka image. So when you send a message to a transport, that
message really needs to be thought of as sacred, like this message must be processed
successfully because if it's not, that could lead to some very weird conditions in
your system. So let's actually fake some handling here. I'm going to open up 
`AddPonkaToImageHandler`, and right before the process here, I'm going to say if 
`rand(0, 10) < 7`, we're going to `throw new \Exception()` that says 

> I failed randomly!!!! 

So most of the time this is actually going to fail

since we just a did a handler here. Um, change. I'm going to stop the worker, restart
it,

```terminal-silent
php bin/console messenger:consume -vv
``` 
 
clear the screen, and then let's upload. How about five photos in here? And then
we'll go back and check out what's happening. Whoa, let's pull this apart here. A lot
happened quickly so you can see the first message was received and it was handled.
The second message was received and it was handled successfully. The third message
was received and an exception occurred was handling it. So I says I failed randomly.
Then down here you says it's retrying it retry number one. And if you actually keep
going down here, you'll see another retrying number one.

And then cause I don't see any other retries, those are actually successful. Then
here we don't see any other failures. So when did his extra retried, those and those
are successful and we can see this refresh. All of those actually have pumpkin them.
So let's actually do a little bit more of an interesting thing here because I want to
see if we can get one of those to fail completely. Before I do that in a clear my
screen on my worker, here we go, ah, and here you can see a retry. Number two, our
retry number three and finally down here it says critical, rejecting it, receive the
message it trying to handle an exception in it says it was removed from the
transport. So let's break. So let's break this down here. One of the really cool
things on messenger is that retry functionality is built into it.

By default, it's going to retry a message three times and then it's going. If it
can't do it three times, then it's going to remove it from the transport and the
message is going to be lost permanently. However, there's a couple of cool things
going on. First of all, in a few minutes we're going to talk about something called a
failure transport, which is something where even after a message fails three times,
you can store it somewhere so you configure out what out, what went wrong and uh,
handling. The second cool thing is it was hard to see with all this output here, but
if you look closely, you can start to see gaps in the messages here. So you can see
retry number three here, happen at the 13 seconds and then there's several messages
being processed at once, but there's at least a two second delay, maybe even a four
second delay before that message is handled.

Again, built into the retry functionality is a delay. So check this out. I'm gonna
open up. I want to stop my worker here and I'm gonna run 

```terminal
php bin/console config:dump framework messenger
```
This is actually going to give us an example of configuration
for anything that is under the framework, not wrong file under the Framework
Messenger key. So if we do that, you can see under here it kind of talks about how
under the transports key every transport can have a retry strategy is actually are
things that you can configure about the retry it like if the Max number of retries
but also the `delay:` that should happen between retries and this is actually really
cool because you see this `multiplier:` down here. This delayed doesn't mean that it
just tries one second between delays. It actually means that it's going to try one
second the first time, but then it's going to use this multiplier here to get
exponentially longer, so one seconds and then it'll delay two seconds and then a
delay for seconds.

This is an important thing because if you have a ta, if your, if your message failed
because it there was some temporary issue connecting to some third party server, then
you might not want to try back on immediately. In fact, you might choose to set these
retries to something much hires that you try back like a minute or five minutes
later. Yeah. That's also run a similar command `debug:config framework messenger` 

```terminal
php bin/console debug:config framework messenger 
```

This tells us what our current configuration is. So you can see we have our aceing
transport here and we have retry strategy and we have Max retries. Three and delay is
a thousand. So just to make this a little more interesting, I'm gonna go into my
handler and I'm going to temporarily make it always fail by adding a `|| true` on
there. Then under messenger, let's actually mess with the retry configuration. Now
you notice async is just set to a string here. There are other things that you can
configure under your transport, and as soon as you need to configure them, you're
going to drop this down and call that key `dsn:`. And now he gets a `retry_strategy`. And
then let's set this to delay 2 seconds.

Oh, I should also mention about the `RetryStrategyInterface` earlier, which is under
the service key. All right, so let's restart our worker process here. `messenger:consume`

```terminal-silent
php bin/console messenger:consume -vv 
```

And this time I'm going to avoid just one photos that we can see what
happens as it fails over and over again. Cool. So he can see it failed once it's
retrying check out the mess and delay here. Nine to 11, 11 to 15, four second delay.
And then yes, the last one is eight seconds later. So two second delay for a second,
the lake and eight second delay. And then, uh, finally rejected it and remove
different, the transports. That message is gone at this point is gone forever. So to
make this a little bit easier, I'm actually gonna change this to lay back to 500, so
it doesn't take so long to fail. And next we're gonna talk something about the
failure transport, which is the place where all messages go when they, when they die,
when they fully fail after they meet the `max_retry` stuff.
