<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

class WincacheAdapter extends AbstractAdapter implements AdapterInterface
{
    protected int $ttl = 0;

    public function __construct(array $config = [])
    {
        // config
        if (isset($config['ttl'])) {
            $this->ttl = (int) $config['ttl'];
        }

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        $result = wincache_ucache_get($this->addNamespaceToKey($key), $success);

        if (!$success) {
            throw new KeyNotFoundException();
        }

        return $result;
    }

    public function getMulti(array $keys): array
    {
        $namespacedKeys = $this->addNamespaceToKeys($keys);
        $namespacedValues = wincache_ucache_get($namespacedKeys, $success);

        if (!$success) {
            return [];
        }

        return $this->removeNamespaceFromKeys($namespacedValues);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): bool
    {
        return wincache_ucache_set($this->addNamespaceToKey($key), $value, $this->ttl);
    }

    public function setMulti(array $data): bool
    {
        $namespacedData = $this->addNamespaceToKeys($data, true);
        $errors = wincache_ucache_add($namespacedData, null, $this->ttl);

        return empty($errors);
    }

    public function contains(string $key): bool
    {
        return wincache_ucache_exists($this->addNamespaceToKey($key));
    }

    public function delete(string $key): bool
    {
        wincache_ucache_delete($this->addNamespaceToKey($key));

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        foreach ($keys as $key) {
            wincache_ucache_delete($this->addNamespaceToKey($key));
        }

        return true;
    }

    public function flush(): bool
    {
        return wincache_ucache_clear();
    }
}
