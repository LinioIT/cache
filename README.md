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

## Cache Not Found Keys

It is now possible (v.1.0.9) to cache not found keys in upper level adapters of the adapter stack. The configuration option `cache_not_found_keys` can be set at the adapter level.

Note that this option, obviously, does not apply to the last level of the cache hierarchy.

## Usage

```php
<?php

use \Linio\Component\Cache\CacheService;

$container['cache'] = new CacheService([
    'namespace' => 'mx',
    'layers' => [
        0 => [
            'adapter_name' => 'array',
            'adapter_options' => [
                'cache_not_found_keys' => true,
                'encoder' => 'json',
            ],
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
                'encoder' => 'serial',
            ],
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
     * @param ?int $ttl Time To Live; store value in the cache for ttl seconds.
     * This ttl overwrites the configuration ttl of the adapter
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null);

    $adapter->set('foo', 'bar');
    $adapter->set('foo', 'bar', 60); // store bar in the cache for 60 seconds

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
- `cache_not_found_keys` *optional* default: false

Requires [APC extension](http://php.net/manual/en/book.apc.php) or [APCu extension](https://pecl.php.net/package/APCu).

----------

### `wincache`

Adapter options:

- `ttl` *optional* default: 0 (unlimited)
- `cache_not_found_keys` *optional* default: false

Requires [WinCache extension](http://www.iis.net/downloads/microsoft/wincache-extension).

----------

### `memcached`

Adapter options:

- `servers` array of memcache servers. format: [[<host1>, <port1>, <weight1>], [<host2>, <port2>, <weight2>], ...]
- `options` array of memcache options. format: [<option_name1> => <value1>, <option_name2> => <value2>, ...] 
- `connection_persistent` *optional* default: false
- `pool_size` *optional* default: 1 (only for persistent connections)
- `ttl` *optional* default: 0 (unlimited)
- `cache_not_found_keys` *optional* default: false

Requires [Memcached extension](http://php.net/manual/en/book.memcached.php).

----------

### `redis`

Adapter options:

- `host` *optional* default: 127.0.0.1
- `port` *optional* default: 6379
- `database` *optional* default: 0 (int)
- `password` *optional* default: null (no password)
- `connection_persistent` *optional* default: false
- `ttl` *optional* default: 0 (unlimited)
- `cache_not_found_keys` *optional* default: false

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
- `read_timeout` *optional* default: 0 (unlimited)
- `retry_interval` *optional* default: 0 (value in milliseconds)
- `ttl` *optional* default: 0 (unlimited)
- `cache_not_found_keys` *optional* default: false
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
- `cache_not_found_keys` *optional* default: false

The `ensure_table_created` is used to ensure the cache table exists in the database. This option has a significant performance impact.


**Not recommended to be used in production environments.**

----------

### `aerospike`

Adapter options:

- `hosts`
- `aerospike_namespace` *optional* default: test
- `persistent` *optional* default: true
- `options` *optional* default: []
- `ttl` *optional* default: 0 (unlimited)
- `cache_not_found_keys` *optional* default: false

For the Aerospike adapter, the *aerospike_namespace* property will be used as the *namespace* in Aerospike, and the *namespace* configuration in the *CacheService* will be used as the *set* in Aerospike.

Requires [Aerospike Extension](https://github.com/aerospike/aerospike-client-php).

----------
