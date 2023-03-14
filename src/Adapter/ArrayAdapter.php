<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

class ArrayAdapter extends AbstractAdapter implements AdapterInterface
{
    protected array $cacheData = [];

    public function __construct(array $config = [])
    {
        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        if (!array_key_exists($this->addNamespaceToKey($key), $this->cacheData)) {
            throw new KeyNotFoundException();
        }

        return $this->cacheData[$this->addNamespaceToKey($key)];
    }

    public function getMulti(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            if (array_key_exists($this->addNamespaceToKey($key), $this->cacheData)) {
                $values[$key] = $this->cacheData[$this->addNamespaceToKey($key)];
            }
        }

        return $values;
    }

    /**
     * @param mixed $value
     * @param ?int $ttl it does not have effect here
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $this->cacheData[$this->addNamespaceToKey($key)] = $value;

        return true;
    }

    public function setMulti(array $data): bool
    {
        foreach ($data as $key => $value) {
            $this->cacheData[$this->addNamespaceToKey($key)] = $value;
        }

        return true;
    }

    public function contains(string $key): bool
    {
        return array_key_exists($this->addNamespaceToKey($key), $this->cacheData);
    }

    public function delete(string $key): bool
    {
        unset($this->cacheData[$this->addNamespaceToKey($key)]);

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->cacheData[$this->addNamespaceToKey($key)]);
        }

        return true;
    }

    public function flush(): bool
    {
        $this->cacheData = [];

        return true;
    }
}
