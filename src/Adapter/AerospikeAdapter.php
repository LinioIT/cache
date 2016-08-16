<?php
declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

class AerospikeAdapter extends AbstractAdapter implements AdapterInterface
{
    const BIN_KEY = 'v';
    const DEFAULT_SET = 'default';

    /**
     * @var \Aerospike
     */
    protected $db;

    /**
     * @var int
     */
    protected $ttl;

    public function __construct(array $config)
    {
        $aerospikeConfig = [];

        if (!isset($config['hosts'])) {
            throw new InvalidConfigurationException('Missing configuration parameter: hosts');
        }

        // default options
        $persistent = true;
        $options = [];
        $this->ttl = 0;

        if (isset($config['persistent'])) {
            $persistent = (bool) $config['persistent'];
        }

        if (isset($config['options'])) {
            $options = $config['options'];
        }

        if (isset($config['ttl'])) {
            $this->ttl = $config['ttl'];
        }

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }

        $aerospikeConfig['hosts'] = $config['hosts'];

        $this->db = new \Aerospike($aerospikeConfig, $persistent, $options);
        if (!$this->db->isConnected()) {
            throw new \RuntimeException("Failed to connect to Aerospike Server [{$this->db->errorno()}]: {$this->db->error()}\n");
        }
    }

    public function get(string $key)
    {
        $namespacedKey = $this->getNamespacedKey($key);
        $status = $this->db->get($namespacedKey, $metadata);

        if ($status != \Aerospike::OK) {
            throw new KeyNotFoundException();
        }

        return $this->removeBin($metadata);
    }

    public function getMulti(array $keys): array
    {
        $namespacedKeys = [];
        foreach ($keys as $key) {
            $namespacedKeys[] = $this->getNamespacedKey($key);
        }

        $status = $this->db->getMany($namespacedKeys, $result);
        if ($status != \Aerospike::OK) {
            return [];
        }

        $values = [];
        foreach ($result as $entry) {
            $key = $entry['key']['key'];
            $value = $this->removeBin($entry);

            if ($value === null) {
                continue;
            }

            $values[$key] = $value;
        }

        return $values;
    }

    public function set(string $key, $value): bool
    {
        $status = $this->db->put($this->getNamespacedKey($key), $this->createBin($value), $this->ttl);

        return ($status == \Aerospike::OK);
    }

    public function setMulti(array $data): bool
    {
        $success = true;
        foreach ($data as $key => $value) {
            $success = $success && $this->set($key, $value);
        }

        return $success;
    }

    public function contains(string $key): bool
    {
        $status = $this->db->exists($this->getNamespacedKey($key), $metadata);

        return ($status == \Aerospike::OK);
    }

    public function delete(string $key): bool
    {
        $namespacedKey = $this->getNamespacedKey($key);

        $this->db->remove($namespacedKey);

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function flush(): bool
    {
        $this->db->scan(
            $this->namespace,
            static::DEFAULT_SET,
            function ($record) {
                unset($record['key']['key']);
                $this->db->remove($record['key']);
            }
        );

        return true;
    }

    protected function getNamespacedKey(string $key)
    {
        return $this->db->initKey($this->namespace, static::DEFAULT_SET, $key);
    }

    protected function createBin($value): array
    {
        return [self::BIN_KEY => $value];
    }

    protected function removeBin($metadata)
    {
        if (!is_array($metadata) || !array_key_exists('bins', $metadata) || !is_array($metadata['bins']) || !array_key_exists(static::BIN_KEY, $metadata['bins'])) {
            return null;
        }

        return $metadata['bins'][static::BIN_KEY];
    }
}
