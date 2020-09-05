Neosolva API client
===================

This component provides the client for all API's powered by **Neosolva**.

Installation
============

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require neosolva/php-api-client
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Usage
-----

The class ```Neosolva\Component\Api\Client``` extends class ```GuzzleHttp\Client```. 
The static method ```create()``` helps you to configure the client.

### Create the client

```php
require_once 'vendor/autoload.php';

use Neosolva\Component\Api\Client; # extends GuzzleHttp\Client

$client = Client::create('https://...', 'username', 'password');
```

### Make a request

```php
# GET operation
$response = $client->get('/foo');

# POST operation
$response = $client->post('/bar', [
    'json' => [
        'qux' => true
    ]
]);
```

Please see [documentation of Guzzle client](http://docs.guzzlephp.org/en/stable/quickstart.html) to 
now khow to use the client.

### Decode JSON response

All API's powered by Neosolva returns contents as JSON. The client provides the method ```decode``` to 
get decoded data of a response as array:

```php
$data = $client->decode($response); # array
```

### Miscellaneous

#### Encode JSON data

The client helps you to encode data as JSON by providing the method ```encode()```:

```php
$json = $client->encode(['foo' => 'bar']); # string
```

#### Retrieve Base URI

The client provides a shortcut to retrieve the configured base URI:

```php
$baseUri = $client->getBaseUri(); # string
```