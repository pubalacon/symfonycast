# Hunting the Final Deprecations

How can we be sure *all* our deprecated code is gone? The easiest way to catch *most*
things is to surf around your site to see if you can trigger any other deprecation
logs. Except... if a deprecation happens on a form submit where you redirect after...
or if it happens on an AJAX call... you're not going to see those on the web debug
toolbar.

## Checking Deprecated Logs Locally

*Fortunately*, deprecations are also logged to a file. At your terminal, run:

```terminal
tail -f var/log/dev.log
```

Symfony writes a *lot* of stuff to this log file... *including* any deprecation
warnings: "User Deprecated". Hit `Ctrl`+`C` to exit the "tail" mode and run this again,
but this time "pipe" it to `grep Deprecated`:

```terminal-silent
tail -f var/log/dev.log | grep Deprecated
```

We're now watching the log file for any lines that contain Deprecated. Unfortunately,
because of that annoying `doctrine/persistence` stuff, it *does* contain extra
noise. But it'll still work. You could filter that out by adding another
`| grep -v persistence`.

Anyways, *now* we can try out the site: like clicking into an article... or doing
anything else you can think of, like going to an admin section `/admin/comments`.
Oh, duh - I'm not logged in as an admin. You get the point: use your site, then
go back and check out the deprecations.

Yikes! I probably *should* have added that `| grep -v persistence` to remove all
the noise. But if you look closely... yea... *every* single one of these
is from `doctrine/persistence`!

So as *best* as we can tell, our site *is* deprecation free. But! There are a few
more things to check to be sure.

## Command Deprecations

For example, if you have some custom console commands, *they* might trigger some
deprecated code. Open a new terminal tab and run:

```terminal
php bin/console
```

This app has two custom console commands. Let's run this `article:stats` command...
it just prints out a fake table:

```terminal
php bin/console article:stats foo
```

It worked perfectly. But if you go back to the logs and look closely... ah! A
*real* deprecation warning!

> `ArticleStatsCommand::execute()` return value should always be of the type
> `int` since Symfony 4.4, NULL returned.

Interesting. Let's open that command: `src/Command/ArticleStatsCommand.php`:

[[[ code('19d8be88dc') ]]]

Since Symfony 4.4, the `execute()` method of every command *must* return an integer.
At the bottom, `return 0`:

[[[ code('224eec8a00') ]]]

This ends up being the "exit code" that the command returns when you run it. Zero
means successful and pretty much anything else - like 1 - means that the command
*failed*.

Copy the return and open the other command class. At the bottom of `execute()`,
`return 0`:

[[[ code('a8ceba0e0b') ]]]

And... let's make sure that we don't have any other return statements earlier.
Nope, it looks good.

## Production Deprecation Log

So we've surfed the site, checked the logs and run some console commands. *Now*
are we sure that all the deprecated code is gone? Maybe? There are 2 final tricks.

First, as I mentioned earlier, at this point, I would deploy my code to production
and watch the `prod.deprecations.log` file for any new entries... ignoring any
`doctrine/persistence` stuff:

[[[ code('df5f77ca53') ]]]

If nothing new is added, it's almost definitely safe to upgrade.

## Deprecations in Tests

Another easy trick is to... run your tests! You... *do* have tests, right? Run:

```terminal
php bin/phpunit
```

For me, it looks like it needs to download PHPUnit... and then... cool! This
*collects* all the deprecations that were hit inside our tests and prints them
when it's done. There are a *lot* of `doctrine/persistence` things... but that's
it! There are no Symfony deprecations.

I am *now* willing to say that our app is ready for Symfony 5.0. So... let's
upgrade next! Thanks to all our hard work, upgrading to a new major version of
Symfony is just a Composer trick.
