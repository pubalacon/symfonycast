# The asset() Function & assets.context

When we go to the show page... of course, it doesn't work yet! We need to update
the template. Copy the `uploaded_asset()` code, open `show.html.twig`...
here it is, and paste.

[[[ code('838e50ff5e') ]]]

Easy! Reload the page now. Oh... it *still* doesn't work. Inspect element on the
image. Ah, the path is right, but because there is no `/` at the beginning,
and because the current URL is a sort of sub-directory, it's looking for the image
in the wrong place. If you hack in the `/`... it pops up!

Adding this opening slash is actually one of the jobs of the `asset()` function.
Try this: wrap this entire thing in `asset()`.

[[[ code('4395bfba76') ]]]

Now refresh. It works! But, wrapping `asset()` around `uploaded_asset()` is kind
of annoying: can't we just handle this internally in `UploaderHelper`? 

[[[ code('bc66b2730a') ]]]

After all, this method is supposed to return the public path to an asset: we 
shouldn't need to do any other "fixes" on the path after.

The easiest way to fix things would be to add a `/` at the beginning. That would
totally work! But... allow me to nerd-out for a minute and explain an edge-case
that the `asset()` function usually handles for us. Imagine if your site were deployed
under a *subdirectory* of a domain. Like, instead of the URL on production being
`thespacebar.com`, it's `thegalaxy.org/thespacebar` - our app does *not* live at
the root of the domain. If you have a situation like this, hardcoding a `/` at the
beginning of the URL won't work! It would need to be `/thespacebar/`.

The `asset()` function does this automatically: it detects that subdirectory and...
just handles it! To *really* make our `getPublicPath()` shine, I want to do the
same thing here.

## Using the RequestStackContext

To do this, we're going to work with a service that you don't see very often in
Symfony: it's the service that's used internally by the `asset()` function to
determine the subdirectory. In the constructor, add another argument:
`RequestStackContext $requestStackContext`. I'll hit `Alt + Enter` and select
initialize fields to create that property and set it.

[[[ code('f30228f8f3') ]]]

Down in `getPublicPath()`, `return $this->requestStackContext->getBasePath()`
and *then* `'/uploads/'.$path`.

[[[ code('b3e0f9f2b7') ]]]

If our app lives at the root of the domain - like it does right now - this will
just return and empty string. But if it lives at a subdirectory like `thespacebar`,
it'll return `/thespacebar`.

Try it! Oh... wow - *huge* error! This `RequestStackContext` service is such a
low-level service, that Symfony doesn't make it available to be used for autowiring.
Check out the error, it says:

> Yo! You can't autowire the `$requestStackContext` argument: it's type-hinted with
> a class called `RequestStackContext`, but there isn't a service with this id.
> Maybe you can create a service alias for this class that points to the
> `assets.context` service.

This is a bit technical and we talk about this in our Symfony Fundamentals course.
Symfony sees that the `RequestStackContext` type-hint is not autowireable, but it
*also* sees that there *is* a service in the container - called `assets.context` -
that is an *instance* of this class!

Check it out: copy the full class name and then go into `config/services.yaml`.
At the bottom, paste the full class name, go copy the service id they suggested,
and say `@assets.context`.

[[[ code('05bbe3670f') ]]]

This creates a service alias. Basically, there is *now* a new service that lives
in the container called `Symfony\Component\Asset\Context\RequestStackContext`.
And if you fetch it, it'll really just give you the `assets.context` service. The
*key* thing is that *this* makes the class autowireable.

To prove it, find your terminal and run:

```terminal
php bin/console debug:autowiring request
```

to search for all autowireable classes that contain that string. Hey! There is our
`RequestStackContext`! If we had run this a minute ago, it would *not* have been
there.

Refresh the page now. Got it! And if you look at the path, yep! It's
`/uploads/article_image/astronaut.jpeg`. If we lived under a subdirectory, that
subdirectory would be there. Small detail, but our site is *still* super portable.

Next, let's create thumbnails of our image so the user doesn't need to download the
full size.
