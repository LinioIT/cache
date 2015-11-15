<?php

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

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [], $lazy = true)
    {
        $this->config = $config;

        if (!$lazy) {
            $this->getClient();
        }
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if (!$this->client instanceof Client) {
            $this->createClient($this->config);
        }

        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $value = $this->getClient()->get($key);

        if ($value === null && !$this->getClient()->exists($key)) {
            throw new KeyNotFoundException();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
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

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if ($this->ttl === null) {
            $result = $this->getClient()->set($key, $value);
        } else {
            $result = $this->getClient()->set($key, $value, static::EXPIRE_RESOLUTION_EX, $this->ttl);
        }

        return $result->getPayload() == 'OK';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setMulti(array $data)
    {
        $responses = $this->getClient()->pipeline(
            function ($pipe) use ($data) {
                foreach ($data as $key => $value) {
                    if ($this->ttl === null) {
                        $pipe->set($key, $value);
                    } else {
                        $pipe->set($key, $value, static::EXPIRE_RESOLUTION_EX, $this->ttl);
                    }
                }
            }
        );

        $result = true;
        foreach ($responses as $response) {
            /* @var $response \Predis\Response\Status */
            $result = $result && ($response->getPayload() == 'OK');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return $this->getClient()->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $this->getClient()->del($key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->getClient()->del($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $result = $this->getClient()->flushAll();

        return $result->getPayload() == 'OK';
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $config
     */
    protected function createClient(array $config)
    {
        $this->client = new Client($this->getConnectionParameters($config), ['prefix' => null]);

        if (isset($config['ttl'])) {
            $this->ttl = $config['ttl'];
        }

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }
    }

    /**
     * @param array $config
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    protected function getConnectionParameters(array $config)
    {
        $connectionParameters = [];
        $connectionParameters['host'] = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $connectionParameters['port'] = isset($config['port']) ? $config['port'] : 6379;
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

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        $this->getClient()->getOptions()->prefix->setPrefix($namespace . ':');
        parent::setNamespace($namespace);
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }
}
