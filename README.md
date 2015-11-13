# Linio Cache
[![Latest Stable Version](https://poser.pugx.org/linio/cache/v/stable.svg)](https://packagist.org/packages/linio/cache) [![License](https://poser.pugx.org/linio/cache/license.svg)](https://packagist.org/packages/linio/cache) [![Build Status](https://secure.travis-ci.org/LinioIT/cache.png)](http://travis-ci.org/LinioIT/cache) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/LinioIT/cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/LinioIT/cache/?branch=master)

Linio Cache is yet another component of the Linio Framework. It aims to
abstract caching by supporting multiple adapters.

## Install


The recommended way to install Linio Cache is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "linio/cache": "dev-master"
    }
}
```

## Tests

To run the test suite, you need install the dependencies via composer, then
run PHPUnit.

    $ composer install
    $ phpunit

## Usage

```php
<?php

use \Linio\Component\Cache\CacheService;

$container['cache'] = new CacheService([
    'namespace' => 'mx',
    'layers' => [
        0 => [
            'adapter_name' => 'array',
            'adapter_options' => [],
        ],
        1 => [
            'adapter_name' => 'apc',
            'adapter_options' => [
                'ttl' => 3600,
            ],
        ],
        2 => [
            'adapter_name' => 'redis',
            'adapter_options' => [
                'host' => 'localhost',
                'port' => 6379,
                'ttl' => 0,
        ],
    ],
]);

$container->setLogger($container['logger']);

```

Note that must provide an adapter name and an array of options. Each adapter has different configuration options.

To start setting data:

```php
<?php

$app['cache.service']->set('foo', 'bar');

```

## Methods

### `get`

```php
<?php

    /**
     * @param string $key
     * @return string value
     */
    public function get($key);

    $adapter->get('foo');

```

### `getMulti`

```php
<?php

     /**
     * @param array $keys
     * @return string[]
     */
    public function getMulti(array $keys);

    $adapter->getMulti(['foo', 'nop']);

```

### `set`

```php
<?php

     /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function set($key, $value);

    $adapter->set('foo', 'bar');

```

### `setMulti`

```php
<?php

     /**
     * @param array $keys
     * @return bool
     */
    public function setMulti(array $data);

    $adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

```

### `delete`

```php
<?php

     /**
     * @param string $key
     * @return bool
     */
    public function delete($key);

    $adapter->delete('foo');

```

### `deleteMulti`

```php
<?php

     /**
     * @param array $keys
     * @return bool
     */
    public function deleteMulti(array $keys);

    $adapter->deleteMulti(['foo', 'fooz']);

```

### `contains`

```php
<?php

     /**
     * @param string $key
     * @return bool
     */
    public function contains($key);

    $adapter->contains('foo');

```

### `flush`

```php
<?php

     /**
     * @return bool
     */
    public function flush();

    $adapter->flush();

```

## Providers

### `array`

This cache does not have any persistence between requests.

**Not recommended to be used in production environments.**

----------

### `apc`

Adapter options:

- `ttl` *optional* default: 0 (unlimited)

Requires [APC extension](http://php.net/manual/en/book.apc.php) or [APCu extension](https://pecl.php.net/package/APCu).

----------

### `wincache`

Adapter options:

- `ttl` *optional* default: 0 (unlimited)

Requires [WinCache extension](http://www.iis.net/downloads/microsoft/wincache-extension).

----------

### `redis`

Adapter options:

- `host` *optional* default: 127.0.0.1
- `port` *optional* default: 6379
- `database` *optional* default: 0 (int)
- `password` *optional* default: null (no password)
- `connection_persistent` *optional* default: false
- `ttl` *optional* default: 0 (unlimited)

More information on the available parameters at the [Predis documentation](https://github.com/nrk/predis/wiki/Connection-Parameters).

----------

### `phpredis`

Adapter options:

- `host` *optional* default: 127.0.0.1
- `port` *optional* default: 6379
- `database` *optional* default: 0 (int)
- `password` *optional* default: null (no password)
- `connection_persistent` *optional* default: false
- `pool_size` *optional* default: 1 (only for persistent connections)
- `timeout` *optional* default: 0 (unlimited)
- `retry_interval` *optional* default: 0 (value in milliseconds)
- `ttl` *optional* default: 0 (unlimited)
- `serializer` *optional* default: none
  - `none` don't serialize data
  - `php` use built-in serialize/unserialize
  - `igbinary` use igBinary serialize/unserialize (requires `igbinary` extension)


More information on the available parameters at the [phpredis documentation](https://github.com/phpredis/phpredis).

Requires [redis extension](https://pecl.php.net/package/redis).

----------

### `mysql`

Using PDO.

Adapter options:

- `host`
- `port`
- `dbname`
- `username`
- `password`
- `table_name`
- `ensure_table_created` *optional* default: false

The `ensure_table_created` is used to ensure the cache table exists in the database. This option has a significant performance impact.


**Not recommended to be used in production environments.**

----------

### `aerospike`

Adapter options:

- `hosts`
- `persistent` *optional* default: true
- `options` *optional* default: []
- `ttl` *optional* default: 0 (unlimited)

Requires [Aerospike Extension](https://github.com/aerospike/aerospike-client-php).

----------
