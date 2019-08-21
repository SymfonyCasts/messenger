# Event & Command Bus Organization

We already organized our new event class into an `Event` subdirectory. Cool! Let's
do the same thing for our commands. Create a new `Command/` sub-directory, move
the two command classes inside... then add `\Command` to the end of the namespace
on both classes.

Let's see... now that we've changed those namespaces... we need to update a few
things. Start in `messenger.yaml`: we're referencing `AddPonkaToImage`. Add
`Command` to that class name. Next, in `ImagePostController`, all the way on top,
we're referencing *both* commands. Update the namespace on each one.

And finally, in the handlers, we have the same thing: each handler has a `use`
statements for the command class it handles. Add the `Command\` namespace on both.

Cool! Let's do the same thing for the handlers: create a new subdirectory called
`Command/`, move those inside... then add the `\Command` namespace to each one.
That's... all we need to change.

I like it! There was nothing technical about this change... it's just a nice way
to organize things if you're planning to use more than just commands - meaning
events or query messages. And everything will work exactly the same way it did
before. To prove it, at your terminal, run `debug:messenger`:

```terminal-silent
php bin/console debug:messenger
```

Yep! We see the same info as earlier.

## Binding Handlers to One Bus

But... now that we've separated our event handlers from our command handlers...
we can do something special: we can *tie* each handler to the *specific* bus that
it's intended for. Again, it's not *super* important to do this, but it'll tighten
things up.

Let me show you: open up `config/services.yaml`. This `App\` line is responsible
for auto-registering every class in the `src/` directory as a service in the container.

The line below *repeats* that for classes in the `Controller/` directory. Why?
This will *override* the controller services registered above and add a special
*tag* that controllers need to work.

We can use a similar trick with Messenger. Say `App\MessageHandler\Command\`,
then use the `resource` key to re-auto-register all the classes in the
`../src/MessageHandler/Command` directory. Whoops - I typo'ed that directory
name - I'll see a *huge* error in a few minutes... and will fix that.

[[[ code('ca10d47952') ]]]


If we *only* did this... absolutely *nothing* would change. This would register
everything in this directory as a service... but that's already done by the
first `App\` entry anyways.

But *now* we can add a tag to this with `name: messenger.message_handler` and
`bus:` set to... the name of my bus from `messenger.yaml`. Copy
`messenger.bus.default` and say `bus: messenger.bus.default`.

[[[ code('d9a5ecd55d') ]]]

There are a few things going on here. First, when Symfony sees a class in our
code that implements `MessageHandlerInterface`, it *automatically* adds this
`messenger.message_handler` tag. *This* is how Messenger knows which classes
are message *handlers*.

We're now adding that tag *manually* so that we can *also* say exactly which *one*
bus this handler should be used on. Without the `bus` option, it's added to *all*
buses.

We also need to add one more key: `autoconfigure: false`.

[[[ code('dc4fd0597b') ]]]

Thanks to the `_defaults` section on top, all services in our `src/` directory
will, by default, have `autoconfigure` *enabled*... which is the feature that's
responsible for automatically adding the `messenger.message_handler` tag to all
services that implement `MessageHandlerInterface`. We're turning it *off* for
services in this directory so that the tag isn't added *twice*.

Phew! You can see the end result by running `debug:messenger` again.

```terminal-silent
php bin/console debug:messenger
```

Oh, the end result is a huge error thanks to my typo! Make sure you're referencing
the `MessageHandler` directory. Try `debug:messenger` again:

```terminal-silent
php bin/console debug:messenger
```

Nice! The event bus *no longer* says that we can dispatch the two commands two
it. What this *really* means is that the command handlers were added to the
command bus, but *not* to the event bus.

Let's repeat this for the events: copy this section, paste, change the
namespace to `Event\`, the directory to `Event` and update the `bus` option to
`event.bus` - the name of our other bus inside `messenger.yaml`.

[[[ code('8805e98abe') ]]]

Cool! Try `debug:messenger` again:

```terminal-silent
php bin/console debug:messenger
```

Perfect! Our two command handlers are bound to the command bus and our one event
handler is tied to the event bus.

Again, doing this last step wasn't *that* important... but I *do* really like
these sub-directories... and tightening things up is nice.

## Renaming the Command Bus

Oh, but while we're cleaning things up, back in `config/packages/messenger.yaml`,
our main bus is called `messenger.bus.default`, which becomes the bus's service
id in the container. We used this name... just because that's the default value
Symfony uses when you have only *one* bus. But because this is a *command* bus,
let's... call it that! Rename it to `command.bus`. And above, use that as our `default_bus`.

[[[ code('f4f8f8d005') ]]]

Where was the old key referenced in our code? Thanks to the fact that we
autowire that service via its type-hint... almost nowhere - just in `services.yaml`.
Change the bus option to `command.bus` as well.

[[[ code('a43eab21de') ]]]

Check everything out by running `debug:messenger` one more time:

```terminal-silent
php bin/console debug:messenger
```

That's nice: two buses, each with a great name and only aware of the correct
handlers.

Oh, and this `AuditMiddleware` is something that we really should also use on
`event.bus`: it logs the journey of messages... which is equally valid here.

[[[ code('74800172ea') ]]]

If you love this organization, great! If it seems like too much, keep it simple.
Messenger is here to do what you want. Next, let's talk about the last type of
message bus: the query bus.
