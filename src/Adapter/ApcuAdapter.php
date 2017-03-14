<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

class ApcuAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var int
     */
    protected $ttl;

    public function __construct(array $config = [])
    {
        // default config
        $this->ttl = 0;

        // config
        if (isset($config['ttl'])) {
            $this->ttl = $config['ttl'];
        }

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }
    }

    public function get(string $key)
    {
        $value = apcu_fetch($this->addNamespaceToKey($key), $success);

        if (!$success) {
            throw new KeyNotFoundException();
        }

        return $value;
    }

    public function getMulti(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $value = apcu_fetch($this->addNamespaceToKey($key), $success);
            if ($success) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    public function set(string $key, $value): bool
    {
        return apcu_add($this->addNamespaceToKey($key), $value, $this->ttl);
    }

    public function setMulti(array $data): bool
    {
        $namespacedData = $this->addNamespaceToKeys($data, true);
        $errors = apcu_add($namespacedData, $this->ttl);

        return empty($errors);
    }

    public function contains(string $key): bool
    {
        return apcu_exists($this->addNamespaceToKey($key));
    }

    public function delete(string $key): bool
    {
        apcu_delete($this->addNamespaceToKey($key));

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        foreach ($keys as $key) {
            apcu_delete($this->addNamespaceToKey($key));
        }

        return true;
    }

    public function flush(): bool
    {
        return apcu_clear_cache();
    }
}
