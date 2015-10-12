<?php

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Redis;

class PhpredisAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var Redis
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
        if (!extension_loaded('redis')) {
            throw new InvalidConfigurationException('PhpRedisAdapter requires "phpredis" extension. See https://github.com/phpredis/phpredis.');
        }

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
        $result = $this->client->get($key);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
    {
        $result = $this->client->mGet($keys);
        $values = [];

        foreach ($keys as $index => $key) {
            if ($result[$index]) {
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
            $result = $this->client->set($key, $value);
        } else {
            $result = $this->client->setex($key, $this->ttl, $value);
        }

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setMulti(array $data)
    {
        if ($this->ttl === null) {
            $result = $this->client->mset($data);
        } else {
            $result = true;
            foreach ($data as $key => $value) {
                $result = $result && $this->client->setex($key, $this->ttl, $value);
            }
        }

        return (bool) $result;
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
        $this->client->delete($key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        $this->client->delete($keys);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return (bool) $this->client->flushDB();
    }

    /**
     * @param Redis $client
     */
    public function setClient(Redis $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $config
     */
    protected function createClient(array $config)
    {
        $params = $this->getConnectionParameters($config);
        $this->client = new Redis();

        if ($params['connection_persistent']) {
            $connectionId = 1;

            if ($params['pool_size'] > 1) {
                $connectionId = mt_rand(1, $params['pool_size']);
            }

            $persistentId = sprintf('%s-%s-%s', $params['port'], $params['database'], $connectionId);
            $this->client->pconnect($params['host'], $params['port'], $params['timeout'], $persistentId, $params['retry_interval']);
        } else {
            $this->client->connect($params['host'], $params['port'], $params['timeout'], null, $params['retry_interval']);
        }

        if ($params['password']) {
            if (!$this->client->auth($params['password'])) {
                throw new InvalidConfigurationException(sprintf('Invalid password for phpredis adapter: %s:%s', $params['host'], $params['port']));
            }
        }

        if ($params['database']) {
            $this->client->select($params['database']);
        }

        if ($params['serializer']) {
            switch ($params['serializer']) {
                case 'php':
                    $this->client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                    break;
                case 'igbinary':
                    if (!extension_loaded('igbinary')) {
                        throw new InvalidConfigurationException('Serializer igbinary requires "igbinary" extension. See https://pecl.php.net/package/igbinary');
                    }

                    if (!defined('Redis:::SERIALIZER_IGBINARY')) {
                        throw new InvalidConfigurationException('Serializer igbinary requires run extension compilation using configure with --enable-redis-igbinary');
                    }

                    $this->client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
                    break;
            }
        }

        $this->client->setOption(Redis::OPT_SCAN, Redis::SCAN_NORETRY);
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function getConnectionParameters(array $config)
    {
        $connectionParameters = [];
        $connectionParameters['host'] = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $connectionParameters['port'] = isset($config['port']) ? $config['port'] : 6379;
        $connectionParameters['password'] = isset($config['password']) ? $config['password'] : null;
        $connectionParameters['database'] = isset($config['database']) ? $config['database'] : 0;
        $connectionParameters['timeout'] = isset($config['timeout']) ? $config['timeout'] : null;
        $connectionParameters['retry_interval'] = isset($config['retry_interval']) ? $config['retry_interval'] : null;
        $connectionParameters['serializer'] = isset($config['serializer']) ? $config['serializer'] : null;
        $connectionParameters['connection_persistent'] = isset($config['connection_persistent']) ? $config['connection_persistent'] : false;
        $connectionParameters['pool_size'] = isset($config['pool_size']) ? $config['pool_size'] : 1;

        return $connectionParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        $this->client->setOption(Redis::OPT_PREFIX, $namespace . ':');
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
