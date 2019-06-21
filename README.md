# Messenger! Queue work for Later

Well hi there! This repository holds the code and script
for the [Messenger! Queue work for Later](https://symfonycasts.com/screencast/messenger) course on SymfonyCasts.

## Setup

If you've just downloaded the code, congratulations!!

To get it working, follow these steps:

**Download Composer dependencies**

Make sure you have [Composer installed](https://getcomposer.org/download/)
and then run:

```
composer install
```

You may alternatively need to run `php composer.phar install`, depending
on how you installed Composer.

**Configure the .env (or .env.local) File**

Open the `.env` file and make any adjustments you need - specifically
`DATABASE_URL`. Or, if you want, you can create a `.env.local` file
and *override* any configuration you need there (instead of changing
`.env` directly).

**Setup the Database**

Again, make sure `.env` is setup for your computer. Then, create
the database & tables!

```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

If you get an error that the database exists, that should
be ok. But if you have problems, completely drop the
database (`doctrine:database:drop --force`) and try again.

**Compiling Webpack Encore Assets**

This tutorial uses [Webpack Encore](https://symfonycasts.com/encore),
which isn't important to understand what's going on, but *is* important
to get our app running. Make sure to install Node and also
[yarn](https://yarnpkg.com). Then run:

```
yarn install
yarn encore dev
```

**Start the built-in web server**

You can use Nginx or Apache, but Symfony's local web server
works even better.

To install the Symfony local web server, follow
"Downloading the Symfony client" instructions found
here: https://symfony.com/download - you only need to do this
once on your system.

Then, to start the web server, open a terminal, move into the
project, and run:

```
symfony serve
```

(If this is your first time using this command, you may see an
error that you need to run `symfony server:ca:install` first).

Now check out the site at `https://localhost:8000`

Have fun!

## The Messenger

My work is loving the world.
Here the sunflowers, there the hummingbird -
equal seekers of sweetness.
Here the quickening yeast; there the blue plums.
Here the clam deep in the speckled sand.
Are my boots old? Is my coat torn?
Am I no longer young and still not half-perfect? Let me
keep my mind on what matters,
which is my work,
which is mostly standing still and learning to be astonished.
The phoebe, the delphinium.
The sheep in the pasture, and the pasture.
Which is mostly rejoicing, since all ingredients are here,
Which is gratitude, to be given a mind and a heart
and these body-clothes,
a mouth with which to give shouts of joy
to the moth and the wren, to the sleepy dug-up clam,
telling them all, over and over, how it is
that we live forever.

[Mary Oliver - Thirst](http://maryoliver.beacon.org/2009/11/thirst/)

## Have Ideas, Feedback or an Issue?

If you have suggestions or questions, please feel free to
open an issue on this repository or comment on the course
itself. We're watching both :).

## Thanks!

And as always, thanks so much for your support and letting
us do what we love!

<3 Your friends at SymfonyCasts
