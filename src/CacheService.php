<?php

declare(strict_types=1);

namespace Linio\Component\Cache;

use Doctrine\Inflector\InflectorFactory;
use Linio\Component\Cache\Adapter\AdapterInterface;
use Linio\Component\Cache\Encoder\EncoderInterface;
use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Cache\Exception\KeyNotFoundException;
use Psr\Log\LoggerInterface;

class CacheService
{
    protected array $adapterStack = [];
    protected EncoderInterface $encoder;
    protected string $namespace = '';
    protected LoggerInterface $logger;
    protected array $cacheConfig = [];

    public function __construct(array $cacheConfig)
    {
        $this->validateServiceConfiguration($cacheConfig);

        $this->cacheConfig = $cacheConfig;

        // Service config
        if (isset($cacheConfig['namespace'])) {
            $this->namespace = $cacheConfig['namespace'];
        }

        if (!isset($cacheConfig['encoder'])) {
            $cacheConfig['encoder'] = 'json';
        }

        $this->createEncoder($cacheConfig['encoder']);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return AdapterInterface[]
     */
    public function getAdapterStack(): array
    {
        if (empty($this->adapterStack)) {
            $this->createAdapterStack($this->cacheConfig);
        }

        return $this->adapterStack;
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        [$value, $success] = $this->recursiveGet($key);

        if (!$success) {
            return;
        }

        return $this->encoder->decode($value);
    }

    /**
     * @return array(mixed, bool)
     */
    protected function recursiveGet(string $key, int $level = 0): array
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

        [$value, $keyFound] = $this->recursiveGet($key, $level + 1);

        if ($keyFound || $adapter->cacheNotFoundKeys()) {
            $adapter->set($key, $value);
        }

        return [$value, $keyFound];
    }

    public function getMulti(array $keys): array
    {
        $values = $this->recursiveGetMulti($keys);

        foreach ($values as $key => $value) {
            $values[$key] = $this->encoder->decode($value);
        }

        return $values;
    }

    protected function recursiveGetMulti(array $keys, int $level = 0): array
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

        return array_merge($values, $notFoundValues);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): bool
    {
        $value = $this->encoder->encode($value);

        return $this->recursiveSet($key, $value);
    }

    /**
     * @param mixed $value
     */
    protected function recursiveSet(string $key, $value, int $level = null): bool
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

    public function setMulti(array $data): bool
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->encoder->encode($value);
        }

        return $this->recursiveSetMulti($data);
    }

    protected function recursiveSetMulti(array $data, int $level = null): bool
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

    public function contains(string $key): bool
    {
        return $this->recursiveContains($key);
    }

    protected function recursiveContains(string $key, int $level = 0): bool
    {
        $adapterStack = $this->getAdapterStack();

        $adapter = $adapterStack[$level];
        $value = $adapter->contains($key);
        if (($value !== false) || ($level == (count($adapterStack) - 1))) {
            return $value;
        }

        return $this->recursiveContains($key, $level + 1);
    }

    public function delete(string $key): bool
    {
        $adapterStack = $this->getAdapterStack();

        foreach ($adapterStack as $adapter) {
            $adapter->delete($key);
        }

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        $adapterStack = $this->getAdapterStack();

        foreach ($adapterStack as $adapter) {
            $adapter->deleteMulti($keys);
        }

        return true;
    }

    public function flush(): bool
    {
        $adapterStack = $this->getAdapterStack();

        foreach ($adapterStack as $adapter) {
            $adapter->flush();
        }

        return true;
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function createAdapterStack(array $cacheConfig): void
    {
        foreach ($cacheConfig['layers'] as $adapterConfig) {
            $this->validateAdapterConfig($adapterConfig);

            $inflector = InflectorFactory::create()->build();
            $adapterClass = sprintf('%s\\Adapter\\%sAdapter', __NAMESPACE__, $inflector->classify($adapterConfig['adapter_name']));

            if (!class_exists($adapterClass)) {
                throw new InvalidConfigurationException('Adapter class does not exist: ' . $adapterClass);
            }

            /** @var AdapterInterface $adapterInstance */
            $adapterInstance = new $adapterClass($adapterConfig['adapter_options']);
            $adapterInstance->setNamespace($this->namespace);

            $this->adapterStack[] = $adapterInstance;
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function createEncoder(string $encoderName): void
    {
        $inflector = InflectorFactory::create()->build();
        $encoderClass = sprintf('%s\\Encoder\\%sEncoder', __NAMESPACE__, $inflector->classify($encoderName));

        if (!class_exists($encoderClass)) {
            throw new InvalidConfigurationException('Encoder class does not exist: ' . $encoderClass);
        }

        /** @var EncoderInterface $encoder */
        $encoder = new $encoderClass();
        $this->encoder = $encoder;
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateAdapterConfig(array $adapterConfig): void
    {
        if (!isset($adapterConfig['adapter_name'])) {
            throw new InvalidConfigurationException('Missing required configuration option: adapter_name');
        }

        if (!isset($adapterConfig['adapter_options'])) {
            throw new InvalidConfigurationException('Missing required configuration option: adapter_options');
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateServiceConfiguration(array $cacheConfig): void
    {
        if (!isset($cacheConfig['layers'])) {
            throw new InvalidConfigurationException('Missing required cache configuration parameter: layers');
        }
    }
}
