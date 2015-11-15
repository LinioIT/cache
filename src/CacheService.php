<?php

namespace Linio\Component\Cache;

use Doctrine\Common\Inflector\Inflector;
use Linio\Component\Cache\Adapter\AdapterInterface;
use Linio\Component\Cache\Encoder\EncoderInterface;
use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Cache\Exception\KeyNotFoundException;

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
     * @var EncoderInterface
     */
    protected $encoder;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $cacheConfig;

    /**
     * @param array $cacheConfig
     */
    public function __construct(array $cacheConfig)
    {
        $this->validateServiceConfiguration($cacheConfig);

        $this->cacheConfig = $cacheConfig;

        // default config
        $this->namespace = '';

        // service config
        if (isset($cacheConfig['namespace'])) {
            $this->namespace = $cacheConfig['namespace'];
        }

        if (!isset($cacheConfig['encoder'])) {
            $cacheConfig['encoder'] = 'json';
        }

        $this->createEncoder($cacheConfig['encoder']);
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
     * @return Adapter\AdapterInterface[]|null
     */
    public function getAdapterStack()
    {
        if ($this->adapterStack === null) {
            $this->createAdapterStack($this->cacheConfig);
        }

        return $this->adapterStack;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        list($value, $success) = $this->recursiveGet($key);

        if (!$success) {
            return;
        }

        return $this->encoder->decode($value);
    }

    /**
     * @param string $key
     * @param int    $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array   [$value, $success]
     */
    protected function recursiveGet($key, $level = 0)
    {
        $adapterStack = $this->getAdapterStack();

        $adapter = $adapterStack[$level];
        $keyFound = true;
        try {
            $value = $adapter->get($key);

            return [$value, $keyFound];
        } catch (KeyNotFoundException $e) {
            $value = null;
            $keyFound = false;
        }

        if ($level == (count($adapterStack) - 1)) {
            return [$value, $keyFound];
        }

        list($value, $keyFound) = $this->recursiveGet($key, $level + 1);

        if (!$keyFound) {
            return;
        }

        $adapter->set($key, $value);

        return [$value, $keyFound];
    }

    /**
     * @param array $keys
     *
     * @return mixed[]
     */
    public function getMulti(array $keys)
    {
        $values = $this->recursiveGetMulti($keys);

        foreach ($values as $key => $value) {
            $values[$key] = $this->encoder->decode($value);
        }

        return $values;
    }

    /**
     * @param array $keys
     * @param int   $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    protected function recursiveGetMulti(array $keys, $level = 0)
    {
        $adapterStack = $this->getAdapterStack();

        $adapter = $adapterStack[$level];
        $values = $adapter->getMulti($keys);

        if (count($values) == count($keys) || ($level == (count($adapterStack) - 1))) {
            return $values;
        }

        $notFoundKeys = [];
        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                $notFoundKeys[] = $key;
            }
        }

        $notFoundValues = $this->recursiveGetMulti($notFoundKeys, $level + 1);
        if (!empty($notFoundValues)) {
            $adapter->setMulti($notFoundValues);
        }

        $values = array_merge($values, $notFoundValues);

        return $values;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        $value = $this->encoder->encode($value);

        return $this->recursiveSet($key, $value);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return bool
     */
    protected function recursiveSet($key, $value, $level = null)
    {
        $adapterStack = $this->getAdapterStack();

        if ($level === null) {
            $level = count($adapterStack) - 1;
        }

        $adapter = $adapterStack[$level];
        $result = $adapter->set($key, $value);

        if ($level == 0) {
            return true;
        }

        if (($result === false) && ($level == count($adapterStack) - 1)) {
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
            $data[$key] = $this->encoder->encode($value);
        }

        return $this->recursiveSetMulti($data);
    }

    /**
     * @param array $data
     * @param int   $level
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    protected function recursiveSetMulti(array $data, $level = null)
    {
        $adapterStack = $this->getAdapterStack();

        if ($level === null) {
            $level = count($adapterStack) - 1;
        }

        $adapter = $adapterStack[$level];
        $result = $adapter->setMulti($data);

        if ($level == 0) {
            return true;
        }

        if (($result === false) && ($level == count($adapterStack) - 1)) {
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
     * @param int    $level
     *
     * @return bool
     */
    protected function recursiveContains($key, $level = 0)
    {
        $adapterStack = $this->getAdapterStack();

        $adapter = $adapterStack[$level];
        $value = $adapter->contains($key);
        if (($value !== false) || ($level == (count($adapterStack) - 1))) {
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
        $adapterStack = $this->getAdapterStack();

        foreach ($adapterStack as $adapter) {
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
        $adapterStack = $this->getAdapterStack();

        foreach ($adapterStack as $adapter) {
            $adapter->deleteMulti($keys);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function flush()
    {
        $adapterStack = $this->getAdapterStack();

        foreach ($adapterStack as $adapter) {
            $adapter->flush();
        }

        return true;
    }

    /**
     * @param array  $cacheConfig
     * @param string $namespace
     *
     * @throws InvalidConfigurationException
     */
    protected function createAdapterStack(array $cacheConfig)
    {
        foreach ($cacheConfig['layers'] as $adapterConfig) {
            $this->validateAdapterConfig($adapterConfig);

            $adapterClass = sprintf('%s\\Adapter\\%sAdapter', __NAMESPACE__, Inflector::classify($adapterConfig['adapter_name']));

            if (!class_exists($adapterClass)) {
                throw new InvalidConfigurationException('Adapter class does not exist: ' . $adapterClass);
            }

            $adapterInstance = new $adapterClass($adapterConfig['adapter_options']);
            /* @var $adapterInstance AdapterInterface */
            $adapterInstance->setNamespace($this->namespace);

            $this->adapterStack[] = $adapterInstance;
        }
    }

    /**
     * @param string $encoderName
     *
     * @throws InvalidConfigurationException
     */
    protected function createEncoder($encoderName)
    {
        $encoderClass = sprintf('%s\\Encoder\\%sEncoder', __NAMESPACE__, Inflector::classify($encoderName));

        if (!class_exists($encoderClass)) {
            throw new InvalidConfigurationException('Encoder class does not exist: ' . $encoderClass);
        }

        $this->encoder = new $encoderClass();
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
