<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;
use Predis\Client;

class RedisAdapter extends AbstractAdapter implements AdapterInterface
{
    const EXPIRE_RESOLUTION_EX = 'ex';
    const EXPIRE_RESOLUTION_PX = 'px';

    /**
     * @var \Predis\Client
     */
    protected $client;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config = [], bool $lazy = true)
    {
        $this->config = $config;

        if (!$lazy) {
            $this->getClient();
        }
    }

    protected function getClient(): Client
    {
        if (!$this->client instanceof Client) {
            $this->createClient($this->config);
        }

        return $this->client;
    }

    public function get(string $key)
    {
        $value = $this->getClient()->get($key);

        if ($value === null && !$this->getClient()->exists($key)) {
            throw new KeyNotFoundException();
        }

        return $value;
    }

    public function getMulti(array $keys): array
    {
        $result = $this->getClient()->mget($keys);
        $values = [];

        foreach ($keys as $index => $key) {
            if ($result[$index] !== null) {
                $values[$key] = $result[$index];
            }
        }

        return $values;
    }

    public function set(string $key, $value): bool
    {
        if ($this->ttl == 0) {
            $result = $this->getClient()->set($key, $value);
        } else {
            $result = $this->getClient()->set($key, $value, static::EXPIRE_RESOLUTION_EX, $this->ttl);
        }

        return $result->getPayload() == 'OK';
    }

    public function setMulti(array $data): bool
    {
        $responses = $this->getClient()->pipeline(
            /** @var $pipe \Predis\Client */
            function ($pipe) use ($data): void {
                foreach ($data as $key => $value) {
                    if ($this->ttl == 0) {
                        $pipe->set($key, $value);
                    } else {
                        $pipe->set($key, $value, static::EXPIRE_RESOLUTION_EX, $this->ttl);
                    }
                }
            }
        );

        $result = true;
        foreach ($responses as $response) {
            /** @var $response \Predis\Response\Status */
            $result = $result && ($response->getPayload() == 'OK');
        }

        return $result;
    }

    public function contains(string $key): bool
    {
        return (bool) $this->getClient()->exists($key);
    }

    public function delete(string $key): bool
    {
        $this->getClient()->del([$key]);

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        $this->getClient()->del($keys);

        return true;
    }

    public function flush(): bool
    {
        $result = $this->getClient()->flushAll();

        return $result->getPayload() == 'OK';
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    protected function createClient(array $config): void
    {
        $this->client = new Client($this->getConnectionParameters($config), ['prefix' => null]);

        if (isset($config['ttl'])) {
            $this->ttl = (int) $config['ttl'];
        }

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }
    }

    protected function getConnectionParameters(array $config): array
    {
        $connectionParameters = [];
        $connectionParameters['host'] = $config['host'] ?? '127.0.0.1';
        $connectionParameters['port'] = $config['port'] ?? 6379;
        if (isset($config['database'])) {
            $connectionParameters['database'] = $config['database'];
        }
        if (isset($config['password'])) {
            $connectionParameters['password'] = $config['password'];
        }
        if (isset($config['connection_persistent'])) {
            $connectionParameters['connection_persistent'] = $config['connection_persistent'];
        }

        return $connectionParameters;
    }

    public function setNamespace(string $namespace): void
    {
        $this->getClient()->getOptions()->prefix->setPrefix($namespace . ':');
        parent::setNamespace($namespace);
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }
}
