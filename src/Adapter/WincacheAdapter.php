<?php

namespace Linio\Component\Cache\Adapter;

class WincacheAdapter extends AbstractAdapter implements AdapterInterface
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
        $result = wincache_ucache_get($this->addNamespaceToKey($key), $success);

        return ($success) ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
    {
        $namespacedKeys = $this->addNamespaceToKeys($keys);
        $namespacedValues = wincache_ucache_get($namespacedKeys, $success);

        if (!$success) {
            return [];
        }

        $values = $this->removeNamespaceFromKeys($namespacedValues);

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        return wincache_ucache_set($this->addNamespaceToKey($key), $value, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function setMulti(array $data)
    {
        $namespacedData = $this->addNamespaceToKeys($data, true);
        $errors = wincache_ucache_add($namespacedData, null, $this->ttl);

        return empty($errors);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return wincache_ucache_exists($this->addNamespaceToKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        wincache_ucache_delete($this->addNamespaceToKey($key));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            wincache_ucache_delete($this->addNamespaceToKey($key));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return wincache_ucache_clear();
    }
}
