<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

abstract class AbstractAdapter
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var bool
     */
    protected $cacheNotFoundKeys = false;

    public function cacheNotFoundKeys(): bool
    {
        return $this->cacheNotFoundKeys;
    }

    protected function addNamespaceToKey(string $key): string
    {
        return $this->namespace . ':' . $key;
    }

    protected function addNamespaceToKeys(array $keys, bool $isKeyValue = false): array
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

    protected function removeNamespaceFromKey(string $key): string
    {
        return substr($key, strlen($this->namespace) + 1);
    }

    protected function removeNamespaceFromKeys(array $keys): array
    {
        $nonNamespacedKeys = [];

        foreach ($keys as $key => $value) {
            $nonNamespacedKeys[$this->removeNamespaceFromKey($key)] = $value;
        }

        return $nonNamespacedKeys;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
    }
}
