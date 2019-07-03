# Logger Channel Setup and Autowiring

Here's our goal... and the end result is going to be pretty cool: to leverage our
middleware - *and* the fact that we're adding this unique id to every message - to
log the entire lifecycle of a message to a new log file. I want to see when a
message - identified by its unique id - was originally dispatched, when it was
sent to the transport, when it was received from the transport and when it was
handled.

## Adding a Log Handler

Before we get into the middleware stuff, let's configure a new logger channel
that logs to a new file. Open up `config/packages/dev/monolog.yaml` and add a
new `channel` key. Wait... that's not right. A logging channel is, sort of a
"category", and the you can control *where* log messages for that category are
sent. We don't want to add it *here* because then it would *only* exist in the
dev environment. Nope, we want the channel to exist in *all* environments.

To do that, directly inside, create a new file called `monolog.yaml`... though...
remember - the actual *names* of these config files aren't important. What *is*
important is to add a `monolog` key, then `channels` set to an array with one new
one - how about `messenger_audit`.

Thanks to that *one* new line, we now have a new logger service in the container
for this channel. Let's find it: at your terminal, run:

```terminal
php bin/console debug:container messenger_audit
```

There it is: `monolog.logger.messenger_audit` - we'll use that in a minute. But
first, I want to make logs to this channel save to a new file in the `dev` environment.
Back up in `config/packages/dev/monolog.yaml`, copy the `main` handler, paste and
change the key to `messenger`... though that could be anything. Change the file
to be called `messenger.log`. Then - here's the magic - instead of saying: log
all messages *except* those in the `event` channel, change this to *only* log
messages in that `messenger_audit` channel.

## Autowiring the Channel Logger

Cool! To use this service, we can't just autowire it by type-hinting the normal
`LoggerInterface`... because that will give us the *main* logger. This is one
of those rare cases where we have *multiple* services in the container that all
use the same class or interface.

To fix this, back in `services.yaml`, add a new global bind:
`$messengerAuditLogger` that points to the new servive - copy that from the terminal,
then paste as `@monolog.logger.messenger_audit`.

Thank to this, if we use the argument name `$messengerAuditLogger` in the constructor
of a service or in a controller, Symfony will pass us that service. By the way,
starting in Symfony 4.2, instead of binding only to the *name* of the argument,
you can also bind to the *type* by saying
`Psr\Log\LoggerInterface: $messengerAuditLogger`. That just makes things a bit more
specific: it would pass us this service for any arguments with this name *and*
the `LoggerInterface` type-hint.

Anyways, we have a new logger channel, that channel will log to a new file, and
the logger service for that channel is autowireable. Time to get to work!

Close up the monolog config files and go to our `AuditMiddleware`. Add a
`public function __construct()` with one argument `LoggerInterface $messengerAuditLogger` -
the same name we used in the config. I'll call the property itself just `$logger`,
and finish this with `$this->logger = $messengerAuditLonger`.

## Setting up the Context

Down in `handle()`, remove the `dump()` and create a new variable called `$context`.
In addition to the actual log *message*, it's a little-known fact that you can
pass extra information... which is super handy! Let's create a key called `id`
set to the unique id, and another called `class` that's set to the class or the
*original* message class. We can get that with `get_class($envelope->getMessage())`.

Let's do the logging next! It's a bit more interesting than you might expect.
How can we figure out if the current message was *just* dispatched or was just
received asynchronously from a transport? And if it was just dispatched, how can
we find out whether or not the message will be handled right now or sent to a
transport for later? The answer... lies in the stamps!
