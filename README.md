# Laravel BigCommerce Webhook Quickstart

This package is a kickstart-style package for Laravel which makes rapidly building and locally
developing Webhook-focused integrations.

## Work in Progress

This module is a work in progress. Stuff might changes—potentially even drastically.

### TODOs

* Clean up the URL handling to make go-live work better.
* Eventify the webhook callback.
* Enable queues for processing webhook callbacks.
* Automate more of the installation (`.env.example` support)
* Move webhook route registration into `routes.php` (similar to auth).
* Got any ideas?

## Prerequisites

This package assumes you have [`ngrok`][0] installed. You only a free account in order
to take advantage of this package. The `ngrok` command line application must be installed and
must be in your path.

## Installation

Require the package in your project:

    composer require vervecommerce/laravel-bigcommerce-webhooks

## Configuration

Before getting started, you'll need to configure the integration. Here is the configuration stub for your `.env` file:

    BIGCOMMERCE_CLIENT_ID=
    BIGCOMMERCE_CLIENT_SECRET=
    BIGCOMMERCE_STORE_HASH=
    BIGCOMMERCE_ACCESS_TOKEN=
    BIGCOMMERCE_API_VERSION=v2
    BIGCOMMERCE_NGROK_URL=
    BIGCOMMERCE_WEBHOOK_SECRET=

You'll also want to publish the configuration file:

    php artisan vendor:publish

This will create a `config/bigcommerce.php` file in the config directory. If you need more information about the config,
there is plenty of documentation in the config file itself.

And then generate a secret key:

    php artisan bigcommerce:generate-key

The secret key is used by the webhook endpoint to validate that the webhook is legitimate.

## oAuth

This module only supports oAuth since it's the only method where webhooks are supported.

BigCommerce also seems to be indicating that support for Basic Auth will be deprecated in the future.

## Usage

### Accessing the BigCommerce API

This package exposes a Facade called `BigCommerce` which proxies calls to either HTTP verbs
(GET, PUT, POST, DELETE) or to Bigcommerce API Client calls themselves. For instance:

```php5
Bigcommerce::get('time'); // Returns a collection object.
Bigcommerce::getTime(); // Returns a DateTime, per the Client.
```

### Local Webhook Development

Using ngrok, one can tunnel a secure endpoint to a local development machine, making building
and testing webhooks locally much more developer-friendly. To facilitate this, a new artisan
command, with a few other pieces of configuration make setting this up a snap.

The only real requirement is that you have `ngrok` already installed on your machine and
somewhere within your `$PATH`. Once you have that, just run:

    $ php artisan ngrok-serve

This will start an `ngrok` process and also run `php artisan serve` in the background. It
will also update a local `.env` value for the ngrok URL which, will be used when creating
new webhooks with BigCommerce.

### Creating and Managing Webhooks

There are several new artisan commands for creating and managing webhooks:

* `bigcommerce:create-webhook` - Creates a webhook.
* `bigcommerce:disable-webhook` - Disables or deletes a webhook.
* `bigcommerce:list-webhooks` - Retrieves and displays configured webhooks and properties.
* `bigcommerce:update-webhooks` - Performs batch updates to webhooks.

## Miscellaneous

This class is loosely based off of [`laravel/big-commerce`](https://github.com/oseintow/laravel-bigcommerce). However,
so much was changed, this is simply becoming a new project. You can access the `connection` and `client` classes by
doing:

```
BigCommerce::$connection; // Gets the connection instance.
\Bigcommerce\Api\Client; // Static class.
```

## Please Refer to BigCommerce API Docs

Again, since this Facade wraps the [BigCommerce PHP API][1], you should refer to that code base for better examples
and documentation.

[0]: https://ngrok.com
[1]: https://github.com/bigcommerce/bigcommerce-api-php
