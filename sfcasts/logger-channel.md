# Logger Channel Setup and Autowiring

Here's our goal... and the end result is going to be pretty cool: leverage our
middleware - *and* the fact that we're adding this unique id to every message - to
log the entire lifecycle of a message to a file. I want to see when a message
was originally dispatched, when it was sent to the transport, when it was received
from the transport and when it was handled.

## Adding a Log Handler

Before we get into the middleware stuff, let's configure a new logger channel
that logs to a new file. Open up `config/packages/dev/monolog.yaml` and add a
new `channels` key. Wait... that's not right. A logging channel is, sort of a
"category", and you can control *how* log messages for each category are handled.
We don't want to add it *here* because then that new channel would *only* exist
in the dev environment. Nope, we want the channel to exist in *all* environments...
even if we decide to only give those messages special treatment in `dev`.

To do that, directly inside `config/packages`, create a new file called
`monolog.yaml`... though... remember - the *names* of these config files aren't
important. What *is* important is to add a `monolog` key, then `channels` set to
an array with one new one - how about `messenger_audit`.

[[[ code('71c5f61745') ]]]

Thanks to this, we now have a new logger service in the container for this channel.
Let's find it: at your terminal, run:

```terminal
php bin/console debug:container messenger_audit
```

There it is: `monolog.logger.messenger_audit` - we'll use that in a minute. But
first, I want to make any logs to this channel save to a new file in the
`dev` environment. Back up in `config/packages/dev/monolog.yaml`, copy the
`main` handler, paste and change the key to `messenger`... though that could be
anything. Update the file to be called `messenger.log` and - here's the magic -
instead of saying: log all messages *except* those in the `event` channel, change
this to *only* log messages that are *in* that `messenger_audit` channel.

[[[ code('4ca2bf0605') ]]]

## Autowiring the Channel Logger

Cool! To use this service, we can't just autowire it by type-hinting the normal
`LoggerInterface`... because that will give us the *main* logger. This is one
of those cases where we have *multiple* services in the container that all
use the same class or interface.

To make it wirable, back in `services.yaml`, add a new global bind:
`$messengerAuditLogger` that points to the service id: copy that from the terminal,
then paste as `@monolog.logger.messenger_audit`.

[[[ code('e8c2081f75') ]]]

Thank to this, if we use an argument named `$messengerAuditLogger` in the constructor
of a service or in a controller, Symfony will pass us that service. By the way,
starting in Symfony 4.2, instead of binding only to the *name* of the argument,
you can also bind to the name *and* type by saying
`Psr\Log\LoggerInterface $messengerAuditLogger`. That just makes things more
specific: Symfony would pass us this service for any arguments that have this name
*and* the `LoggerInterface` type-hint.

Anyways, we have a new logger channel, that channel will log to a special file,
and the logger service for that channel is wirable. Time to get to work!

Close up the monolog config files and go to `AuditMiddleware`. Add a
`public function __construct()` with one argument `LoggerInterface $messengerAuditLogger` -
the same name we used in the config. I'll call the property itself `$logger`,
and finish this with `$this->logger = $messengerAuditLogger`.

[[[ code('315b4fbc04') ]]]

## Setting up the Context

Down in `handle()`, remove the `dump()` and create a new variable called `$context`.
In addition to the actual log *message*, it's a little-known fact that you can
pass extra information to the logger... which is *super* handy! Let's create a
key called `id` set to the unique id, and another called `class` that's set to
the class of the *original* message class. We can get that with
`get_class($envelope->getMessage())`.

[[[ code('68ef1f5d3f') ]]]

Let's do the logging next! It's a bit more interesting than you might expect.
How can we figure out if the current message was just *dispatched* or was just
*received* asynchronously from a transport? And if it was just dispatched, how can
we find out whether or not the message will be handled right now or sent to a
transport for later? The answer... lies in the stamps!
