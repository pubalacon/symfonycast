# Flysystem: Streaming & Defensive Coding

There are a few minor problems with our new `Flysystem` integration. Let's clean
them up before they bite us!

## Streaming

The first is that using `file_get_contents()` eats memory: it reads the entire
contents of the file into PHP's memory. That's not a huge deal for tiny files,
but it *could* be a big deal if you start uploading bigger stuff. And, it's just
not necessary.

For that reason, in general, when you use `Flysystem`, instead of using methods
like `->write()` or `->update()`, you should use `->writeStream()` or `->updateStream()`.

[[[ code('ff9b354af8') ]]]

It works the same, except that we need to pass a *stream* instead of the contents.
Create the stream with `$stream = fopen($file->getPathname())` and, because we just
need to *read* the file, use the `r` flag. Now, pass stream instead of the contents.

[[[ code('30e8034c3f') ]]]

Yea... that's it! Same thing, but no memory issues. But we *do* need to add one
more detail after: if `is_resource($stream)`, then `fclose($stream)`. The "if"
is needed because *some* Flysystem adapters close the stream by themselves.

[[[ code('4faedff08c') ]]]

## Deleting the Old File

Ok, for problem number two, go back to `/admin/article`. Log back in with password
`engage`, edit an article, and go select an image - how about `astronaut.jpg`. Hit
update and... it works! So what's the problem? Well, we just *replaced* an existing
image with this new one. Does the old file still exist in our uploads directory?
Absolutely! But it probably shouldn't. When an article image is updated, let's
delete the old file.

In `UploaderHelper`, add a second argument - a *nullable* string argument called
`$existingFilename`. 

[[[ code('703291b6f7') ]]]

This is nullable because sometimes there may *not* be an existing
file to delete. At the bottom, it's beautifully simple: if an `$existingFilename`
was passed, then `$this->filesystem->delete()` and pass that
the full path, which will be `self::ARTICLE_IMAGE.'/'.$existingFilename`.

[[[ code('cca413e8af') ]]]

Done! You can see the astronaut file that we're using right now. Oh, but first,
head over to `ArticleAdminController`: we need to pass this new argument.
Let's see - this is the `edit()` action - so pass `$article->getImageFilename()`.

[[[ code('9ad4ef0b1f') ]]]

In `new()`, you can really just pass `null` - there will *not* be an article image.
But I'll pass `getImageFilename()` to be consistent.

[[[ code('8f025c8616') ]]]

Oh, and there's one other place we need update: `ArticleFixtures`. Down here, just
pass `null`: we are never updating.

[[[ code('b9bc84baa6') ]]]

Try it! Here is the current astronaut image. Now, move over, upload `rocket.jpg`
this time and update! Back in the directory... there's rocket and astronaut is gone!
Love it!

## Avoiding Errors

In a *perfect* system, the existing file will *always* exist, right? I mean,
how could a filename get set on the entity... without being uploaded? Well, what
if we're developing locally... and maybe we clear out the uploads directory to
test something - or we clear out the uploads directory in our automated tests.
What would happen?

Let's find it! Empty `uploads/`. Back in our browser, the image preview still
shows up because this is rendering a thumbnail file - which we didn't delete -
but the original image is totally gone. Select `earth.jpeg`, update and... it fails! It fails on `$this->filesystem->delete()`.

This *may* be the behavior you want: if something weird happens and the old file
is gone, *please* explode so that I know. But, I'm going to propose something slightly
less hardcore. If the old file doesn't exist for some reason, I don't want the entire
process to fail... it really doesn't need to.

The error from Flysystem is a `FileNotFoundException` from  `League\Flysystem`.
In `UploaderHelper` wrap that line in a try-catch. Let's catch that
`FileNotFoundException` - the one from `League\Flysystem`

[[[ code('8abab61864') ]]]

## Logging Problems

That'll fix that problem... but I don't *love* doing this. Honestly, I *hate*
silencing errors. One of the benefits of throwing an exception is that we can
configure Symfony to notify us of errors via the logger. At SymfonyCasts, we send
all errors to a Slack channel so we know if something weird is going on... not that
we *ever* have bugs. Pfff.

Here's what I propose: a *soft* failure: we don't fail, but we *do* log that an
error happened. Back on the constructor, autowire a new argument:
`LoggerInterface $logger`. I'll hit `Alt + Enter` and select initialize fields to
create that property and set it. 

[[[ code('dabaec96ae') ]]]

Now, down in the catch, say `$this->logger->alert()` - alert is one of the highest 
log levels and I usually send all logs that are this level or higher to a Slack channel. 
Inside, how about: "Old uploaded file %s was missing when trying to delete" - and pass
`$existingFilename`.

[[[ code('acdc9b7922') ]]]

Thanks to this, the user gets a smooth experience, but *we* get notified so we
can figure out how the heck the old file disappeared.

Move over and re-POST the form. *Now* it works. And to prove the log worked,
check out the terminal tab where we're running the Symfony web server: it's streaming
all of our logs here. Scroll up and... there it is!

> Old uploaded file "rocket..." was missing when trying to delete

## Checking for Filesystem Failure

Ok, there's *one* more thing I want to tighten up. If one of the calls to the
`Filesystem` object fails... what do you think will happen? An exception? Hold
Command or Ctrl and click on `writeStream()`. Check out the docs: we *will* get
an exception if we pass an invalid stream or if the file already exists. But for
any other type of failure, maybe a network error... instead of an exception, the
method just returns false!

Actually, that's not *completely* true - it depends on your adapter. For example,
if you're using the S3 adapter and there's a network error, it *may* throw its
own type of exception. But the point is this: if any of the Filesystem methods
fail, you might *not* get an exception: it might just return false.

For that reason, I like to code defensively. Assign this to a `$result` variable.

[[[ code('229a8579d4') ]]]

Then say: `if ($result === false)`, let's throw our own exception - I *do* want
to know that something failed:

> Could not write uploaded file "%s"

and pass `$newFilename`. 

[[[ code('bb7fdafb53') ]]]

Copy that and do the same for `delete`:

> Could not delete old uploaded file "%s"

with `$existingFilename`. 

[[[ code('c1eb5d642a') ]]]

I'm *throwing* this error instead of just logging something because this would *truly* 
be an exceptional case - we shouldn't let things continue. But, it's your call.

Let's make sure this all works: move over and select the `stars` file - or...
actually the "Earth from Moon" photo. Update and... got it!

Next: let's teach LiipImagineBundle to play nice with Flysytem. After all, if we
move Flysystem to S3, but LiipImagineBundle is still looking for the source files
locally... well... we're not going to have a great time.
