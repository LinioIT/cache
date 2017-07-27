<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;
use Linio\Component\Cache\Exception\KeyNotFoundException;
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
     * @var array
     */
    protected $config;

    public function __construct(array $config = [], bool $lazy = true)
    {
        if (!extension_loaded('redis')) {
            throw new InvalidConfigurationException('PhpRedisAdapter requires "phpredis" extension. See https://github.com/phpredis/phpredis.');
        }

        $this->config = $config;

        if (!$lazy) {
            $this->getClient();
        }
    }

    protected function getClient(): Redis
    {
        if (!$this->client instanceof Redis) {
            $this->createClient($this->config);
        }

        return $this->client;
    }

    public function get(string $key)
    {
        $result = $this->getClient()->get($key);

        if ($result === false && !$this->getClient()->exists($key)) {
            throw new KeyNotFoundException();
        }

        return $result;
    }

    public function getMulti(array $keys): array
    {
        $result = $this->getClient()->mGet($keys);
        $values = [];

        foreach ($keys as $index => $key) {
            if ($result[$index]) {
                $values[$key] = $result[$index];
            }
        }

        return $values;
    }

    public function set(string $key, $value): bool
    {
        if ($this->ttl === null) {
            $result = $this->getClient()->set($key, $value);
        } else {
            $result = $this->getClient()->setex($key, $this->ttl, $value);
        }

        return (bool) $result;
    }

    public function setMulti(array $data): bool
    {
        if ($this->ttl === null) {
            $result = $this->getClient()->mset($data);
        } else {
            $result = true;
            foreach ($data as $key => $value) {
                $result = $result && $this->client->setex($key, $this->ttl, $value);
            }
        }

        return (bool) $result;
    }

    public function contains(string $key): bool
    {
        return $this->getClient()->exists($key);
    }

    public function delete(string $key): bool
    {
        $this->client->delete($key);

        return true;
    }

    public function deleteMulti(array $keys): bool
    {
        $this->client->delete($keys);

        return true;
    }

    public function flush(): bool
    {
        return (bool) $this->getClient()->flushDB();
    }

    public function setClient(Redis $client)
    {
        $this->client = $client;
    }

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
            $this->client->pconnect($params['host'], $params['port'], $params['timeout'] ?? 0, $persistentId, $params['retry_interval'] ?? 0);
        } else {
            $this->client->connect($params['host'], $params['port'], $params['timeout'] ?? 0, '', $params['retry_interval'] ?? 0);
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
                case 'none':
                    $this->client->setOption(Redis::OPT_SERIALIZER, (string) Redis::SERIALIZER_NONE);
                    break;
                case 'php':
                    $this->client->setOption(Redis::OPT_SERIALIZER, (string) Redis::SERIALIZER_PHP);
                    break;
                case 'igbinary':
                    if (!extension_loaded('igbinary')) {
                        throw new InvalidConfigurationException('Serializer igbinary requires "igbinary" extension. See https://pecl.php.net/package/igbinary');
                    }

                    if (!defined('Redis::SERIALIZER_IGBINARY')) {
                        throw new InvalidConfigurationException('Serializer igbinary requires run extension compilation using configure with --enable-redis-igbinary');
                    }

                    $this->client->setOption(Redis::OPT_SERIALIZER, (string) Redis::SERIALIZER_IGBINARY);
                    break;
            }
        }

        $this->client->setOption(Redis::OPT_SCAN, (string) Redis::SCAN_NORETRY);

        if (isset($config['ttl'])) {
            $this->ttl = $config['ttl'];
        }

        if (isset($config['cache_not_found_keys'])) {
            $this->cacheNotFoundKeys = (bool) $config['cache_not_found_keys'];
        }

        if (isset($params['read_timeout'])) {
            $this->client->setOption(Redis::OPT_READ_TIMEOUT, (string) $params['read_timeout']);
        }
    }

    protected function getConnectionParameters(array $config): array
    {
        $connectionParameters = [];
        $connectionParameters['host'] = $config['host'] ?? '127.0.0.1';
        $connectionParameters['port'] = $config['port'] ?? 6379;
        $connectionParameters['password'] = $config['password'] ?? null;
        $connectionParameters['database'] = $config['database'] ?? 0;
        $connectionParameters['timeout'] = $config['timeout'] ?? null;
        $connectionParameters['read_timeout'] = $config['read_timeout'] ?? null;
        $connectionParameters['retry_interval'] = $config['retry_interval'] ?? null;
        $connectionParameters['serializer'] = $config['serializer'] ?? null;
        $connectionParameters['connection_persistent'] = $config['connection_persistent'] ?? false;
        $connectionParameters['pool_size'] = $config['pool_size'] ?? 1;

        return $connectionParameters;
    }

    public function setNamespace(string $namespace)
    {
        $this->getClient()->setOption(Redis::OPT_PREFIX, $namespace . ':');
        parent::setNamespace($namespace);
    }

    public function setTtl(int $ttl)
    {
        $this->ttl = $ttl;
    }
}
