Neosolva PHP API client
=======================

[![Build Status](https://travis-ci.org/Neosolva/php-api-client.svg?branch=master)](https://travis-ci.org/Neosolva/php-api-client) 
[![Latest Stable Version](https://poser.pugx.org/neosolva/php-api-client/v/stable)](https://packagist.org/packages/neosolva/php-api-client) 
[![Latest Unstable Version](https://poser.pugx.org/neosolva/php-api-client/v/unstable)](https://packagist.org/packages/neosolva/php-api-client) 
[![Total Downloads](https://poser.pugx.org/neosolva/php-api-client/downloads)](https://packagist.org/packages/neosolva/php-api-client)

This component helps you to create a client for an API powered by **Neosolva**.

The authentication resides on [Basic HTTP authentication](https://fr.wikipedia.org/wiki/Authentification_HTTP). 
The username and the API key is provided by your sales partner.

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

Please see [documentation of Guzzle client](http://docs.guzzlephp.org/en/stable/quickstart.html) to 
now khow to use the client.

### Create the client

```php
require_once 'vendor/autoload.php';

use Neosolva\Component\Api\Client; # extends GuzzleHttp\Client

$client = Client::create('https://...', 'username', 'password');
```

### Make a request

```php
# 
# GET operation
#

$response = $client->get('/foo');

# 
# POST operation
#

$data = [
    'foo' => 'bar',
    'baz' => 'qux'
];

$response = $client->post('/bar', [
    'json' => $data
]);
```

The response is an instance of interface ```Psr\Http\Message\ResponseInterface```.

### Decode JSON response

All API's powered by Neosolva returns contents as JSON. The client provides the method ```decode()``` to 
get decoded data from a response:

```php
$data = $client->decode($response); # array
```

### Miscellaneous

#### Retrieve Base URI

The client provides a shortcut to retrieve the configured base URI:

```php
$baseUri = $client->getBaseUri(); # string
```