<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

class ApcAdapter extends AbstractAdapter implements AdapterInterface
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
        $value = apc_fetch($this->addNamespaceToKey($key), $success);

        if (!$success) {
            throw new KeyNotFoundException();
        }

        return $value;
    }

    public function getMulti(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $value = apc_fetch($this->addNamespaceToKey($key), $success);
            if ($success) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return apc_store($this->addNamespaceToKey($key), $value, $ttl ?? $this->ttl);
    }

    public function setMulti(array $data): bool
    {
        $namespacedData = $this->addNamespaceToKeys($data, true);
        $errors = apc_store($namespacedData, $this->ttl);

        return empty($errors);
    }

    public function contains(string $key): bool
    {
        return apc_exists($this->addNamespaceToKey($key));
    }

    public function delete(string $key): bool
    {
        apc_delete($this->addNamespaceToKey($key));

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        foreach ($keys as $key) {
            apc_delete($this->addNamespaceToKey($key));
        }

        return true;
    }

    public function flush(): bool
    {
        return apc_clear_cache('user');
    }
}
