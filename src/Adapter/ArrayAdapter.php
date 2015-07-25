<?php

namespace Linio\Component\Cache\Adapter;

class ArrayAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $cacheData;

    // @codingStandardsIgnoreStart
    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        $this->cacheData = [];
    }
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (array_key_exists($this->addNamespaceToKey($key), $this->cacheData)) {
            return $this->cacheData[$this->addNamespaceToKey($key)];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
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
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->cacheData[$this->addNamespaceToKey($key)] = $value;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setMulti(array $data)
    {
        foreach ($data as $key => $value) {
            $this->cacheData[$this->addNamespaceToKey($key)] = $value;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return isset($this->cacheData[$this->addNamespaceToKey($key)]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->cacheData[$this->addNamespaceToKey($key)]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            unset($this->cacheData[$this->addNamespaceToKey($key)]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->cacheData = [];

        return true;
    }
}
