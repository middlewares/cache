# middlewares/cache

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]

Middleware components with the following cache utilities:

* [CachePrevention](#cacheprevention)
* [Expires](#expires)
* [Cache](#cache)

## Requirements

* PHP >= 7.2
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/cache](https://packagist.org/packages/middlewares/cache).

```sh
composer require middlewares/cache
```

## CachePrevention

To add the response headers for cache prevention. Useful in development environments:

```php
Dispatcher::run([
    new Middlewares\CachePrevention()
]);
```

## Expires

This middleware adds the `Expires` and `Cache-Control: max-age` headers to the response. You can configure the cache duration for each mimetype. If it's not defined, [use the defaults](src/expires_defaults.php).

```php
// Use the default configuration
$expires = new Middlewares\Expires();

// Custom durations
$expires = new Middlewares\Expires([
    'text/css' => '+1 year',
    'text/js' => '+1 week',
]);
```

### defaultExpires

Set the default expires value if the request mimetype is not configured. By default is 1 month. Example:

```php
//set 1 year lifetime to css and js
$durations = [
    'text/css' => '+1 year',
    'text/javascript' => '+1 year',
];

//and 1 hour to everything else
$default = '+1 hour';

$expires = (new Middlewares\Expires($durations))->defaultExpires($default);
```

## Cache

Saves the response headers in a [PSR-6 cache pool](http://www.php-fig.org/psr/psr-6/) and returns `304` responses (Not modified) if the response is still valid. This saves server resources and bandwidth because the body is returned empty. It's recomended to combine it with `Expires` to set the lifetime of the responses.

```php
$cachePool = new Psr6CachePool();

Dispatcher::run([
    new Middlewares\Cache($cachePool),
    new Middlewares\Expires()
]);
```

Optionally, you can provide a `Psr\Http\Message\ResponseFactoryInterface` as the second argument to create the `304` empty responses. If it's not defined, [Middleware\Utils\Factory](https://github.com/middlewares/utils#factory) will be used to detect it automatically.

```php
$cachePool = new Psr6CachePool();
$responseFactory = new MyOwnResponseFactory();

$cache = new Middlewares\Cache($cachePool, $responseFactory);
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/cache.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/cache/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/cache.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/cache.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/cache
[link-travis]: https://travis-ci.org/middlewares/cache
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/cache
[link-downloads]: https://packagist.org/packages/middlewares/cache
