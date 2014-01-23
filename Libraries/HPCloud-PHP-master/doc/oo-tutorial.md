Tutorial: Using HPCloud-PHP           {#oo-tutorial}
=================

HPCloud-PHP provides PHP language bindings for the HPCloud APIs. HPCloud
is an OpenStack-based cloud service offering a wide (and ever-expanding)
variety of services.

In this tutorial, we will walk through the process of creating a simple
tool that interacts with HP Cloud's Object Storage. The emphasis in this
article is on getting started and learning the concepts, not building a
polished product.

**This tutorial focuses on the object-oriented API.** The other way to
work with this library is through the stream wrapper. That topic is
covered in [another tutorial](@ref streams-tutorial).

## Pre-flight Check

HPCloud-PHP has been developed to require PHP 5.3 or later. You are
strongly encouraged to also install the CURL PHP extension. Many
distributions of PHP come with this enabled. Sometimes, though, you may
need to do something like `apt-get php5-curl` or similar. (Don't take
our word for it -- check your system's documentation.)

You can check for both of these conditions by checking the output of
`php --info` (on the commandline) or `<?php phpinfo(); ?>`.

### Check the pilot, too!

In our pre-flight check, we would be remiss if we didn't point out that
there are some requirements for the pilot (that's you), too.

The HPCloud library is composed of two parts:

1. The Object-Oriented part, which is the subject of this tutorial.
2. The Stream Wrapper, which is the subject of another tutorial.

The object-oriented library makes ample use of PHP namespaces. If you've
never seen these before, they look like this:

~~~{.php}
<?php
\HPCloud\Storage\ObjectStorage\RemoteObject
?>
~~~

The namespace above is read like this: "The RemoteObject class is part
of the ObjectStorage package in the Storage package in the base HPCloud
package." Those familiar with Java, Python, and other languages will
recognize this way of talking (though the backslash is an unfortunate
symbol choice).

For our library, we followed the recommendation of SPR-0, which means
that the class above can be found in the file at:

~~~
src/HPCloud/Storage/ObjectStorage/RemoteObject.php
~~~

The pattern of matching namespace to file name should (we hope) make it
easier for you to navigate our code.

If this namespace stuff continues to confuse you, you may want to take a
look at [the PHP documentation](http://us3.php.net/manual/en/language.namespaces.php),
or you may just prefer to keep on reading and learn by example. We don't
do anything really fancy with namespaces.

**In this document, we sometimes replace the backslash (\\) with double
colons (`::`) so that links are automatically generated.** So
`\HPCloud\Bootstrap` may appear as HPCloud::Bootstrap. The reason for
this is [explained elsewhere](@ref styleguide).

## Step 1: Getting the Library

You can get the HPCloud-PHP library at the [HPCloud GitHub
Repository](https://github.com/hpcloud). The latest code is always
available there.

The project also uses [Composer](http://packagist.org/), and this is the
best method for adding HPCloud-PHP to your PHP project.

For our example, we will assume that the library is accessible in the
default include path, so the following line will include the
`Bootstrap.php` file:

~~~{.php}
include 'HPCloud/Bootstrap.php';
~~~

## Step 2: Bootstrap the Library

The first thing to do in your application is make sure the HPCloud
library is bootstrapped. When we say "bootstrap", what we really mean is
letting the library initialize itself.

Bootstrapping does not always involve any manual interaction on your
part. If you are using an SPR-0 autoloader that knows of the HPCloud
directory, that is enough for the system to initialize itself.

Sometimes, though, you will need to bootstrap HPCloud in your own code,
and this is done as follows:

~~~{.php}
<?php
require_once 'HPCloud/Bootstrap.php';

use \HPCloud\Bootstrap;
use \HPCloud\Services\IdentityServices;
use \HPCloud\Storage\ObjectStorage;
use \HPCloud\Storage\ObjectStorage\Object;

\HPCloud\Bootstrap::useAutoloader();
?>
~~~

The first line should be self-explanatory: We require the main
`Bootstrap.php` file (which contains the `Bootstrap` class).

After that, we declare a list of namespaced objects that we will use.
This way we can refer to them by their short name, rather than by their
fully qualified name.

The last line initializes the built-in HPCloud autoloader. What does
this mean? It means that this is the only `require` or `include`
statement you need in your code. The library does the rest of the
including for you, on demand, in a performance-sensitive way.

There are some other fancy things that HPCloud::Bootstrap can do for
you. Most notably, you can pass configuration parameters into it. But
for the time being, we are good to go.

Our library is boostrapped. Next up: Let's connect to our account.

## Step 3: Connecting

Our programming goal, in this tutorial, is to interact with the Object
Storage service on HP Cloud. (Object Storage is, for all intents and
purposes, basically a service for storing files in the cloud.)

But before we can interact directly with Object Storage, we need to
authenticate to the system. And to do this, we need the following four
pieces of information:

- Account ID: The account number.
- Secret key: The shared secret that, when paired with account number,
  authenticates the client.
- Tenant ID: An identifier that maps an account to a set of services.
  (In theory at least, one account can have multiple tenant IDs, and one 
  tenant ID can be linked to multiple accounts.)
- Endpoint URL: The URL to the Identity Services endpoint at HPCloud.

Before you issue a forlorn sigh, envisioning some laborious task, let us
point out that all of this information is available in one place, Log
into [the console](https://console.hpcloud.com) and go to the `API Keys`
page. It's all there.

### Identity Services

The HPCloud is composed of numerous services. There's the Compute
service, the Object Storage service, the CDN service... and so on.

Authenticating separately to each of these would be a collosal waste of
network resources. And behind the scenes, account management would be
difficult on the server side.

That's where Identity Services comes in. It is a central service that
handles all things authorization and authentication related. Roughly,
it works as follows:

- The client sends an authentication request
- If it fails, the service returns an error
- If authentication succeeds, the service returns a time-sensitive token
  (basically a shared secret) and a "service catalog"

The *token* is valid for some fixed period of time (say, 30 minutes),
during which time it can be used for every other service. Each request
to an HPCloud service should send (along with other info) the token. The
remote service then validates the token with identity services, saving
our app the trouble of making another round trip.

The *service catalog* lists all of the HPCloud services that the present
account can access.

### Authenticating

With that little bit of theory behind us, we can now go about
authenticating.

~~~{.php}
<?php
$account = 'ADD ACCOUNT HERE';
$key = 'ADD KEY HERE';
$tenantId = 'ADD TENANT ID HERE';
$endpoint = 'ADD ENDPOINT URL HERE';

$idService = new \HPCloud\Services\IdentityServices($endpoint);
$token = $idService->authenticateAsAccount($account, $key, $tenantId);
?>
~~~

Assuming the variables above have been set to include valid data, this
script can connect to HPCloud and authenticate.

When we construct a new HPCloud::Services::IdentityServices object, we must pass it the
endpoint URL for HPCloud Identity Services. Typically, that URL will
look something like this:

~~~
https://region-a.geo-1.identity.hpcloudsvc.com:35357
~~~

The `authenticateAsAccount()` method will authenticate to the
Identity Services endpoint. For convenience, it returns the
authorization token (`$token`), though we can also get the token from
`$idService->token()`.

Note that the `IdentityServices` object may throw various exceptions
(all subclasses of HPCloud::Exception) during authentication. Failed
authentication results in an HPCloud::Transport::AuthorizationException, while
a network failure may result in an HPCloud::Transport::ServerException.

Earlier, we talked about the service catalog. Once we've authenticated,
we can get the service catalog from `$idService->serviceCatalog()`. It
is an associative array, and you can get an idea of what it contains by
dumping it with `var_dump()`, should you so desire.

At this point, we have what we need from Identity Services. It's time to
look at Object Storage.

### IdentityServices in a Nutshell

Instances of HPCloud::Services::IdentityServices are responsible for:

- Authentication
- Accessing the service catalog
- Accessing account info
- Associating tenant IDs with accounts (advanced)

## Step 4: Connecting to Object Storage

The Object Storage system is concerned with two classes of things:

- An Object: A self-contained bundle of data (back in my day, we called
  them "files").
- A Container: A storage container (bucket) for objects.

Your object storage can have any number of containers, and each
container can have any number of objects.

In the object model for the HPCloud PHP library, a top-level object
called HPCloud::Storage::ObjectStorage provides access to the Object
Storage service. In this step, we will be working with that object.

### Getting an ObjectStorage Instance

Earlier, we created an `IdentityServices` instance called `$idService`.
We will use that here to get the service catalog. Once we have the
catalog, we can have a new `ObjectStorage` instance created for us,
configured to talk to our account's Object Storage instance in the
HPCloud. Along with the service catalog, we also need our token that
shows the Object Storage endpoint that we have already authenticated to
Identity Services. Earlier, we captured that value in the `$token`
variable.

Now we can get a new HPCloud::Storage::ObjectStorage instance:

~~~{.php}
<?php
$catalog = $idService->serviceCatalog();

$store = ObjectStorage::newFromServiceCatalog($catalog, $token);

// UPDATE: As of Beta 6, you can use newFromIdentity():
// $store = ObjectStorage::newFromIdentity($idService);
?>
~~~

First we get the service catalog (`$catalog`), and then we use the
`ObjectStorage::newFromServiceCatalog()` static method to create the new
Object Storage instance.

The pattern of using a constructor-like static function is used
throughout the HPCloud PHP library. Inspired by Objective-C constructors 
and the Factory design pattern, it makes it possible for a single class
to have multiple constructors.

In particular, many top-level classes provide a
`newFromServiceCatalog()` constructor function, since these classes know
how to construct instances from a service catalog, thus freeing the
developer up from knowing the details of a service catalog entry.

Now we have an `ObjectStorage` instance that is already configured to
talk to our HPCloud object storage service. Next, we can create a
container.

### ObjectStorage in a Nutshell

Instances of HPCloud::Storage::ObjectStorage are responsbile for:

- Providing high-level information about the Object Storage service
- Creating, deleting, loading, and listing Containers
- Modifying Container ACLs
- Attaching a HPCloud::Storage::CDN service object to a Container (advanced)

## Step 5: Adding a Container

Before we can start putting objects (files) into our Object Storage
service, we need a place to put them. An Object Storage service can hold
numerous containers (and each container can have different access
controls -- a topic we won't get into here).

Containers are represented in the library by the
HPCloud::Storage::ObjectStorage::Container class. And creating a
container is done by a method on the `ObjectStorage` object that we
created above:

~~~{.php}
<?php
$store->createContainer('Example');
$container = $store->container('Example');
?>
~~~

Recall that `$store` is the name of our `ObjectStorage` instance. In the
first of the two lines above, we create a new container named `Example`.
Then in the second line, we get that container.

Why is this two steps? The answer is that the HPCloud PHP library mimics
the architecture of the underlying API. This is two operations (which
means it requires two network requests to the remote host), and so we
must perform two operations.

The `createContainer()` call actually creates the new container on the
cloud's Object Storage. The second call connects to the remote object
storage, and gets the new container. The container that is returned will
have some additional information, such as the amount of space it takes
up on the remote storage, and the access control rules for that
container. All of this information will be available on the `$container`
instance.

Our `$container` instance is an instance of
HPCloud::Storage::ObjectStorage::Container. This object can be used not
only to find out about a container, but also to get information about
the objects in that container.

Now that we have a `Container`, we can add an object.

### The Container in a Nutshell

(Yes, we realize the irony of that title.)

A HPCloud::Storage::ObjectStorage::Container instance is responsible for the following:

- Accessing information about the container
- Creating, saving, deleting, and listing objects in the container
- Providing path-like traversal of objects
- Copying objects across containers
- Interacting with CDN objects (advanced)
- Loading a lightweight representation of an object without fetching the
  entire object (more on this later).

Among the features of a Container, it can act like an `Iterator` and is
`Countable`. That means you can loop through a Container in a `foreach`
loop and also use `count($container)` to find out the number of objects
in a Container.

## Step 6: Storing an Object

Now we are ready to create an object, and then store it in our
container.

Before diving too deeply, it is important to point out a detail: When
working with a remote data storage service, we are typically working
with a local copy and a remote copy. If our code isn't constructed
correctly, it is possible for these two to get out of sync.

Earlier, we created a container directly on the remote side, and then
fetched the container. As we create an object, we are going to do the
opposite: We will create a local object, and then save it to the remote
storage. Later, we will fetch the remote object.

~~~{.php}
<?php
$name = 'hello.txt';
$content = 'Hello World';
$mime = 'text/plain';

$localObject = new Object($name, $content, $mime);
$container->save($localObject);
?>
~~~

In the code above, we create `$localObject` with a `$name`, some
`$content`, and a `$mime` type. Strictly speaking, only `$name` is
required.

The HPCloud::Storage::ObjectStorage::Object class is used primarily to
describe a locally created object. Once we have our new `Object`, we can
save it remotely using the `save()` method on our `$container` object.
This will push the object to the remote object storage service.

While we can continue manipulating `$localObject`, we are working with
the local version, not the latest version of what's on the server. This
is fine if what we are doing is writing more data. However, when
examining the content of the object, remember that we are working with
the local copy, and its properties may differ from the remote copy's.

### What if I call save() twice with the same Object?

Objects, like files on a file system, are referenced by name. Any time
you `save()` an object, it will be pushed to the remote object storage
server, which will happily replace the old content with your newly
submitted content.

Next let's turn to loading objects from the remote object storage.

### The Object in a Nutshell

The HPCloud::Storage::ObjectStorage::Object instances are used for:

- Creating a local object to be stored remotely

This class is also the base class for the `RemoteObject` class that we
will look at later.

The API is generally constructed so that a developer needn't worry about
the differences between an `Object` and a `RemoteObject`. But in all but
the edgiest of edge cases, you would only create an instance of
`Object`, never of `RemoteObject`.

## Step 7: Loading an Object

Containers not only provide the methods for saving objects, but also for
loading objects. Thus, we can fetch the object that we just created:

~~~{.php}
<?php
$object = $container->object('hello.txt');

printf("Name: %s \n", $object->name());
printf("Size: %d \n", $object->contentLength());
printf("Type: %s \n", $object->contentType());
print $object->content() . PHP_EOL;
?>
~~~

The `$object` variable now references an instance of a
HPCloud::Storage::ObjectStorage::RemoteObject that contains the entire
object. `RemoteObject` represents an object that was loaded from the
remote server. Along with providing the features of the `Object` class
we saw earlier, it also provides numerous optimizations for working over
the network.

Now that we have the object, we print out several pieces of information
-- `name()`, `size()`, amd `type()`. Then, using `content()`, we fetch
the content of the object.

### Lazily Loading an Object

The method we used above to fetch the object is perfect for our needs.
It pulls the entire object down in a single request. But imagine this
scenario: Our object storage has large media files, and we don't know
at loading time whether or not we need to access the body content, or
just the other data about the object.

It would be a time-consuming task to download the entire body of a large
media file if we don't actually use the body. On the other hand, from an
API standpoint it is great to be able to pass around a single object,
and not require the application to know whether or not the body has been
retrieved.

The `RemoteObject` solves this problem using a technique known as "lazy
loading". That is, it can pull some of the data right away, but defer
fetching the rest of the data until that data is actually needed.

To fetch an object this way, we can just swap out one line in the
example above:

~~~{.php}
<?php
$object = $container->proxyObject('hello.txt');

printf("Name: %s \n", $object->name());
printf("Size: %d \n", $object->contentLength());
printf("Type: %s \n", $object->contentType());
print $object->content() . PHP_EOL;
?>
~~~

Instead of using `object()`, we now use `proxyObject()`. This method
immediately loads the core data about the remote object, but defers
fetching the content until the content is requested.

In the example above, then, one network request is issued by
`proxyObject()`, but another is initiated when `$object->content()` is
called.

### The RemoteObject in a Nutshell

Instances of a HPCloud::Storage::ObjectStorage::RemoteObject offer the following features:

- Access to an object stored on the remote object storage
- A proxying mechanism for lazily loading objects
- Support for loading via CDN (advanced)
- A stream-based API for using stream and file-based PHP functions
- Automatic tempfile-based caching for large objects (using
  `php://temp`).

`RemoteObject` instances can be updated and then passed to
`Container::save()` to update the copy on the server, too.

## Summary

At this point we have created a very basic script that connects to
HPCloud and works with object storage. Clearly, this only scratches the
surface of what the HPCloud PHP library does. But hopefully this is
enough to get you started with the library.

The entire library is well documented, and the documentation is
[available online](https://github.com/hpcloud). You can also build a
local copy by installing [doxygen](http://www.stack.nl/~dimitri/doxygen)
(if you haven't already) and running `make docs` in the root of the
HPCloud PHP project. This will place the generated documents in
`docs/api/html`.

\see oo-tutorial-code.php
