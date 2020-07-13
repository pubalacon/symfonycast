# Embedded Images

Look book at the HTML source. When we added the logo earlier, we added it as
a normal `img` tag. The only thing special was that we needed to use the
`absolute_url` function in Twig to make sure the URL contained our domain.

## Linking versus Embedding Images

It turns out that there are *two* ways to put an image into an email. The first is
this one: a normal, boring `img` tag that links to your site. The *other* option
is to *embed* the image inside the email itself.

There are pros and cons to both. For example, if you link directly to an image
on your site... and you delete that image... if the user opens up the email,
that image will be broken. But... the fact that you're linking to an image on
your site... means that you could *change* the image... and it would change on all
the emails.

We'll talk more about *when* you should link to an image versus embed an image
in a few minutes. But first, let's see *how* we can *embed* this logo.

Remember, the *source* logo image is located at `assets/images/email/logo.png`.
This is the *physical* file we want to embed.

## Adding a Twig Path to Images

How do we do that? We're going to do it *entirely* from inside of Twig with
a special function that *points* to that image.

But to do this, we need a way to *refer* to the image file from inside of Twig.
We're going to do that by adding a new twig *path*. Open up
`config/packages/twig.yaml`... and I'll close a few files.

One of the config keys you can put under `twig` is called *paths*... and
it's *super* cool. Add one new "path" below this: `assets/images` - I'm
literally referring to the `assets/images` directory - set to the word... how
about... `images`. That part could be anything.

[[[ code('82601793d0') ]]]

Ok... so *what* did this just do? Forget about emails *entirely* for a minute.
Out-of-the-box, when you render a template with Twig, it knows to look for that
file in the `templates/` directory... and *only* in the `templates/` directory.
If you have template files that live somewhere else, *that* is where "paths"
are handy. For example, pretend that, for *some* crazy reason, we decided to put
a template inside the `assets/images/` directory called `dark-energy.html.twig`.
Thanks to the item we added under `paths`, we could *render* that template by
using a special path `@images/dark-energy.html.twig`.

This feature is referred to as "namespaced Twig paths". You configure *any*
directory, set it to a string "namespace" - like `images` - then refer to that
directory from twig by using `@` then the namespace.

## Embedding an Image

In our case, we're not planning to put a *template* inside the `assets/images/`
directory and render it. But we *can* leverage the Twig path to refer to the
*logo* file.

Back in the template, remove *all* the asset stuff that was pointing to the logo.
Replace it with `{{ email.image() }}`. Remember, the `email` variable is an instance
of this `WrappedTemplatedEmail` class. We're literally calling this `image()` method:
we pass it the physical path to an image file, and it takes care of *embedding* it.

What's the *path* to the logo file? It's `@images/email/logo.png`.

[[[ code('4752c27b66') ]]]

Yep, thanks to our config, `@images` points to `assets/images`, and then we put
the path after that - `email/logo.png`.

## The "cid" and how Images are Embedded

So... what difference does this make in the final email? Let's find out! Go back
to the site and do our normal thing to re-submit the registration form. Over in
Mailtrap... ok cool - the email *looks* exactly the same. The difference is hiding
in the HTML source. Woh! Instead of the image `src` being a URL that points to
our site... it's some weird `cid:` then a long string.

This is *great* email nerdery. Check out the "Raw" tab. We already know that the
content of the email has multiple parts: here's the text version, below is the
`text/html` version and... below *that*, there is now a *third* part of the
email content: the logo image! It has a `Content-ID` header - this long `cfdf933`
string - and then the image contents below.

The `Content-Id` is the *key*. Inside the message itself, *that* is what the
`cid` is referring to. This tells the mail client to go find that "part" of the
original message and display it here.

So it's kind of like an email attachment, except that it's displayed *within*
the email. We'll talk about *true* email attachments later.

## Linking Versus Embedding

So, which method should we use to add images to an email: linking or embedding?
Oof, that's a tough question. Embedding an image makes it more robust: if the
source image is deleted or your server isn't available, it still shows up. It
also makes the email "heavier". This *can* be a problem: if the *total* size
of an email gets too big - even 100kb - it *could* start to affect deliverability:
a bigger size sometimes counts against your email's SPAM score. Deliverability
is an art, but this is something to be aware of.

Some email clients will also make a user click a
"Show images from sender" link before displaying *linked* images... but they will
display embedded images immediately. But I've also seen some inconsistent handling
of embedded images in gmail.

So... the general rule of thumb... if there is one, is this: if you need to
include the same image for everyone - like a logo or anything that's part of the
email's layout - *link* to the image. But if what you're displaying is *specific*
to that email - like the email is showing you a photo that was just shared with
your account on the site - theni you can embed the image, if it's small. When you
embed, the image doesn't need to be hosted publicly anywhere because it's literally
contained *inside* the email.

Next, I already mentioned that the `style` tag doesn't work in gmail... which means
that our email will be *completely* unstyled for anyone using gmail. That's...
a huge problem. To fix this, *every* style you need *must* be attached directly
to the element that needs it via a `style` attribute... which is *insane*! But
no worries - Mailer can help, with something called CSS inlining.
