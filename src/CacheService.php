<?php

namespace Linio\Component\Cache;

use Linio\Component\Cache\Adapter\AdapterInterface;
use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Util\String;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CacheService
{
    /**
     * @var AdapterInterface[]
     */
    protected $adapterStack;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param array $cacheConfig
     */
    public function __construct(array $cacheConfig)
    {
        // default config
        $this->namespace = '';

        // service config
        if (isset($cacheConfig['namespace'])) {
            $this->namespace = $cacheConfig['namespace'];
        }

        $this->validateServiceConfiguration($cacheConfig);

        $this->createAdapterStack($cacheConfig['layers'], $this->namespace);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
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

    /**
     * @param string $key
     *
     * @return string
     */
    public function get($key)
    {
        return json_decode($this->recursiveGet($key), true);
    }

    /**
     * @param string $key
     * @param int $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return mixed
     */
    protected function recursiveGet($key, $level = 0)
    {
        $adapter = $this->adapterStack[$level];
        $value = $adapter->get($key);

        if (($value !== null) || ($level == (count($this->adapterStack) - 1))) {
            return $value;
        }

        $value = $this->recursiveGet($key, $level + 1);

        if ($value === null) {
            return null;
        }

        $adapter->set($key, $value);

        return $value;
    }

    /**
     * @param array $keys
     *
     * @return string[]
     */
    public function getMulti(array $keys)
    {
        $values = $this->recursiveGetMulti($keys);

        foreach ($values as $key => $value) {
            $values[$key] = json_decode($value, true);
        }

        return $values;
    }

    /**
     * @param array $keys
     * @param int $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    protected function recursiveGetMulti(array $keys, $level = 0)
    {
        $adapter = $this->adapterStack[$level];
        $values = $adapter->getMulti($keys);

        if (count($values) == count($keys) || ($level == (count($this->adapterStack) - 1))) {
            return $values;
        }

        $notFoundKeys = [];
        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                $notFoundKeys[] = $key;
            }
        }

        $notFoundValues = $this->recursiveGetMulti($notFoundKeys, $level + 1);
        $adapter->setMulti($notFoundValues);

        $values = array_merge($values, $notFoundValues);

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
        $value = json_encode($value);

        return $this->recursiveSet($key, $value);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return bool
     */
    protected function recursiveSet($key, $value, $level = null)
    {
        if ($level === null) {
            $level = count($this->adapterStack) - 1;
        }

        $adapter = $this->adapterStack[$level];
        $result = $adapter->set($key, $value);

        if ($level == 0) {
            return true;
        }

        if (($result === false) && ($level == count($this->adapterStack) - 1)) {
            return false;
        }

        return $this->recursiveSet($key, $value, $level - 1);
    }

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function setMulti(array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = json_encode($value);
        }

        return $this->recursiveSetMulti($data);
    }

    /**
     * @param array $data
     * @param int $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    protected function recursiveSetMulti(array $data, $level = null)
    {
        if ($level === null) {
            $level = count($this->adapterStack) - 1;
        }

        $adapter = $this->adapterStack[$level];
        $result = $adapter->setMulti($data);

        if ($level == 0) {
            return true;
        }

        if (($result === false) && ($level == count($this->adapterStack) - 1)) {
            return false;
        }

        return $this->recursiveSetMulti($data, $level - 1);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function contains($key)
    {
        $value = $this->recursiveContains($key);

        return $value;
    }

    /**
     * @param string $key
     * @param int $level
     *
     * @return bool
     */
    protected function recursiveContains($key, $level = 0)
    {
        $adapter = $this->adapterStack[$level];
        $value = $adapter->contains($key);
        if (($value !== false) || ($level == (count($this->adapterStack) - 1))) {
            return $value;
        }

        $value = $this->recursiveContains($key, $level + 1);

        return $value;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        foreach ($this->adapterStack as $adapter) {
            $adapter->delete($key);
        }

        return true;
    }

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function deleteMulti(array $keys)
    {
        foreach ($this->adapterStack as $adapter) {
            $adapter->deleteMulti($keys);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function flush()
    {
        foreach ($this->adapterStack as $adapter) {
            $adapter->flush();
        }

        return true;
    }

    /**
     * @param array $cacheConfig
     * @param string $namespace
     *
     * @throws InvalidConfigurationException
     */
    protected function createAdapterStack(array $cacheConfig, $namespace)
    {
        foreach ($cacheConfig as $adapterConfig) {
            $this->validateAdapterConfig($adapterConfig);

            $adapterClass = sprintf('%s\\Adapter\\%sAdapter', __NAMESPACE__, String::pascalize($adapterConfig['adapter_name']));

            if (!class_exists($adapterClass)) {
                throw new InvalidConfigurationException('Adapter class does not exist: ' . $adapterClass);
            }

            $adapterInstance = new $adapterClass($adapterConfig['adapter_options']);
            /* @var $adapterInstance AdapterInterface */
            $adapterInstance->setNamespace($namespace);

            $this->adapterStack[] = $adapterInstance;
        }
    }

    /**
     * @param $adapterConfig
     *
     * @throws InvalidConfigurationException
     */
    protected function validateAdapterConfig($adapterConfig)
    {
        if (!isset($adapterConfig['adapter_name'])) {
            throw new InvalidConfigurationException('Missing required configuration option: adapter_name');
        }

        if (!isset($adapterConfig['adapter_options'])) {
            throw new InvalidConfigurationException('Missing required configuration option: adapter_options');
        }
    }

    /**
     * @param array $cacheConfig
     *
     * @throws InvalidConfigurationException
     */
    protected function validateServiceConfiguration(array $cacheConfig)
    {
        if (!isset($cacheConfig['layers'])) {
            throw new InvalidConfigurationException('Missing required cache configuration parameter: layers');
        }
    }
}
