<?php

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

class ApcAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var int
     */
    protected $ttl;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        // default config
        $this->ttl = 0;

        // config
        if (isset($config['ttl'])) {
            $this->ttl = $config['ttl'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $value = apc_fetch($this->addNamespaceToKey($key), $success);

        if (!$success) {
            throw new KeyNotFoundException();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
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
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        return apc_add($this->addNamespaceToKey($key), $value, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function setMulti(array $data)
    {
        $namespacedData = $this->addNamespaceToKeys($data, true);
        $errors = apc_add($namespacedData, $this->ttl);

        return empty($errors);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return apc_exists($this->addNamespaceToKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        apc_delete($this->addNamespaceToKey($key));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            apc_delete($this->addNamespaceToKey($key));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return apc_clear_cache('user');
    }
}
