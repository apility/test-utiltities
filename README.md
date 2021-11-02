# Laravel mock tool

This is a simple utility library for bootstrapping a minimal Laravel like container.

This can be used to test your Laravel packages without bootstrapping a full Laravel instance.
You just specify upfront what config and service providers to load, and this library takes care of the rest.

## Installation

```bash
composer require apility/test-utilities
```

## Example

```php
use Apility\Testing\Laravel;

$app = Laravel::createApplication()
    ->withRoot(__DIR__)
    ->withConfig([
        'cache' => [
            'default' => 'file',
            'stores.file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/cache',
            ]
        ],
    ])
    ->withProvider(Illuminate\Cache\CacheServiceProvider::class)
    ->run();
```
