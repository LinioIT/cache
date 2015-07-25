<?php

namespace Linio\Component\Cache\Adapter;

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

        $this->createClient($config);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->client->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
    {
        $result = $this->client->mget($keys);
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
        $result = $this->client->set($key, $value, static::EXPIRE_RESOLUTION_EX, $this->ttl);

        return $result->getPayload() == 'OK';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setMulti(array $data)
    {
        $responses = $this->client->pipeline(
            function ($pipe) use ($data) {
                foreach ($data as $key => $value) {
                    $pipe->set($key, $value, static::EXPIRE_RESOLUTION_EX, $this->ttl);
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
        return $this->client->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $this->client->del($key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->client->del($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $result = $this->client->flushAll();

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
        $this->client->getOptions()->prefix->setPrefix($namespace . ':');
        parent::setNamespace($namespace);
    }
}
