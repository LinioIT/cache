<?php

namespace Linio\Component\Cache\Adapter;

abstract class AbstractAdapter
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @param string $key
     *
     * @return string
     */
    protected function addNamespaceToKey($key)
    {
        return $this->namespace . ':' . $key;
    }

    /**
     * @param array $keys
     * @param bool  $isKeyValue
     *
     * @return array
     */
    protected function addNamespaceToKeys(array $keys, $isKeyValue = false)
    {
        $namespacedKeys = [];

        foreach ($keys as $key => $value) {
            if ($isKeyValue) {
                $namespacedKeys[$this->addNamespaceToKey($key)] = $value;
            } else {
                $namespacedKeys[$key] = $this->addNamespaceToKey($value);
            }
        }

        return $namespacedKeys;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function removeNamespaceFromKey($key)
    {
        return substr($key, strlen($this->namespace) + 1);
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    protected function removeNamespaceFromKeys(array $keys)
    {
        $nonNamespacedKeys = [];

        foreach ($keys as $key => $value) {
            $nonNamespacedKeys[$this->removeNamespaceFromKey($key)] = $value;
        }

        return $nonNamespacedKeys;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }
}
