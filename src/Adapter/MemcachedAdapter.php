<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Cache\Exception\KeyNotFoundException;
use Memcached;

class MemcachedAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var Memcached
     */
    protected $memcached;

    public function __construct(array $config = [])
    {
        $this->validateMemcacheConfiguration($config);

        // default config
        $this->ttl = 0;

        // config
        if (isset($config['ttl'])) {
            $this->ttl = $config['ttl'];
        }

        $persistentId = null;
        if (isset($config['connection_persistent']) && $config['connection_persistent']) {
            $persistentId = (string) 1;

            if (isset($config['pool_size']) && $config['pool_size'] > 1) {
                $persistentId = (string) mt_rand(1, $config['pool_size']);
            }
        }

        $this->memcached = new Memcached($persistentId);
        $this->memcached->addServers($config['servers']);

        if (isset($config['options']) && !empty($config['options'])) {
            $this->memcached->setOptions($config['options']);
        }

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }
    }

    public function get(string $key)
    {
        $value = $this->memcached->get($this->addNamespaceToKey($key));

        if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND) {
            throw new KeyNotFoundException();
        }

        return $value;
    }

    public function getMulti(array $keys): array
    {
        $namespacedKeys = $this->memcached->getMulti($this->addNamespaceToKeys($keys));

        return $this->removeNamespaceFromKeys($namespacedKeys);
    }

    public function set(string $key, $value): bool
    {
        return $this->memcached->set($this->addNamespaceToKey($key), $value, $this->ttl);
    }

    public function setMulti(array $data): bool
    {
        $namespacedData = $this->addNamespaceToKeys($data, true);

        return $this->memcached->setMulti($namespacedData, $this->ttl);
    }

    public function contains(string $key): bool
    {
        try {
            $this->get($key);
        } catch (KeyNotFoundException $exception) {
            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        $this->memcached->delete($this->addNamespaceToKey($key));

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        $namespacedKeys = $this->addNamespaceToKeys($keys);

        $this->memcached->deleteMulti($namespacedKeys);

        return true;
    }

    public function flush(): bool
    {
        return $this->memcached->flush();
    }

    protected function validateMemcacheConfiguration(array $config)
    {
        if (!array_key_exists('servers', $config)) {
            throw new InvalidConfigurationException('Missing configuration parameter: servers');
        }

        if (!is_array($config['servers'])) {
            throw new InvalidConfigurationException('Invalid configuration parameter: servers');
        }

        foreach ($config['servers'] as $server) {
            if (!is_array($server) || count($server) < 2 || count($server) > 3 || !is_numeric($server[1]) || (isset($server[2]) && !is_numeric($server[2]))) {
                throw new InvalidConfigurationException('Invalid configuration parameter: servers');
            }
        }

        if (array_key_exists('options', $config) && !is_array($config['options'])) {
            throw new InvalidConfigurationException('Invalid configuration parameter: options');
        }

        if (isset($config['ttl']) && !is_numeric($config['ttl'])) {
            throw new InvalidConfigurationException('Invalid configuration parameter: ttl');
        }
    }
}
