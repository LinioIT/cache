<?php

namespace Linio\Component\Cache\Adapter;

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

    /**
     * @param array $config
     */
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

        $aerospikeConfig['hosts'] = $config['hosts'];

        $this->db = new \Aerospike($aerospikeConfig, $persistent, $options);
        if (!$this->db->isConnected()) {
            throw new \RuntimeException("Failed to connect to Aerospike Server [{$this->db->errorno()}]: {$this->db->error()}\n");
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function get($key)
    {
        $namespacedKey = $this->getNamespacedKey($key);
        $status = $this->db->get($namespacedKey, $metadata);

        return ($status == \Aerospike::OK) ? $this->removeBin($metadata) : null;
    }

    /**
     * @param array $keys
     *
     * @return string[]
     */
    public function getMulti(array $keys)
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
        foreach ($result as $key => $metadata) {
            if ($metadata === null) {
                continue;
            }
            $values[$key] = $this->removeBin($metadata);
        }

        return $values;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        $status = $this->db->put($this->getNamespacedKey($key), $this->createBin($value), $this->ttl);

        return ($status == \Aerospike::OK);
    }

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function setMulti(array $data)
    {
        $success = true;
        foreach ($data as $key => $value) {
            $success = $success && $this->set($key, $value);
        }

        return $success;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function contains($key)
    {
        $status = $this->db->exists($this->getNamespacedKey($key), $metadata);

        return ($status == \Aerospike::OK);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        $namespacedKey = $this->getNamespacedKey($key);

        $this->db->remove($namespacedKey);

        return true;
    }

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function flush()
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

    /**
     * @param string $key
     *
     * @return array
     */
    protected function getNamespacedKey($key)
    {
        return $this->db->initKey($this->namespace, static::DEFAULT_SET, $key);
    }

    /**
     * @param mixed $value
     *
     * @return array
     */
    protected function createBin($value)
    {
        return [self::BIN_KEY => $value];
    }

    /**
     * @param mixed $metadata
     *
     * @return mixed
     */
    protected function removeBin($metadata)
    {
        if (!is_array($metadata) || !array_key_exists('bins', $metadata) || !array_key_exists(static::BIN_KEY, $metadata['bins'])) {
            return null;
        }

        return $metadata['bins'][static::BIN_KEY];
    }
}
