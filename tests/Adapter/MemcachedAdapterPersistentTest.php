<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @requires extension memcached
 */
class MemcachedAdapterPersistentTest extends TestCase
{
    protected MemcachedAdapter $adapter;
    protected string $namespace;

    protected function setUp(): void
    {
        $this->adapter = new MemcachedAdapter($this->getMemcachedPersistentTestConfiguration());
        $this->namespace = 'mx';
        $this->adapter->setNamespace($this->namespace);
        $this->adapter->flush();
    }

    public function testIsCreatingPersistentConnection(): void
    {
        /** @var $client \Memcached */
        $client = $this->getInstanceProperty($this->adapter, 'memcached');
        $this->assertTrue($client->isPersistent());
    }

    public function testIsRespectingPoolSize(): void
    {
        $connection = new MemcachedAdapter($this->getMemcachedPersistentTestConfiguration());
        $client1 = $this->getInstanceProperty($connection, 'memcached');
        /** @var $client1 \Memcached */
        $stats = $client1->getStats();
        $serverStats = reset($stats);
        $initialConnections = $serverStats['curr_connections'];

        $connections = [];
        for ($i = 1; $i <= 100; $i++) {
            $connection = new MemcachedAdapter($this->getMemcachedPersistentTestConfiguration());
            $connections[] = $connection;
        }

        $client100 = $this->getInstanceProperty($connection, 'memcached');
        /** @var $client100 \Memcached */
        $stats = $client100->getStats();
        $serverStats = reset($stats);
        $currentConnections = $serverStats['curr_connections'];

        $this->assertLessThanOrEqual(15, $currentConnections - $initialConnections);
    }

    public function testIsSettingAndGetting(): void
    {
        $setResult = $this->adapter->set('foo', 'bar');
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    public function testIsGettingInexistentKey(): void
    {
        $this->expectException(KeyNotFoundException::class);

        $actual = $this->adapter->get('foo');
    }

    public function testIsFindingKey(): void
    {
        $this->adapter->set('foo', 'bar');

        $actual = $this->adapter->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsNotFindingKey(): void
    {
        $this->adapter->set('foo', 'bar');

        $actual = $this->adapter->contains('baz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys(): void
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys(): void
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $actual = $this->adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingMultipleKeys(): void
    {
        $actual = $this->adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
        $this->assertEquals('bar', $this->adapter->get('foo'));
        $this->assertEquals('baz', $this->adapter->get('fooz'));
    }

    public function testIsDeletingKey(): void
    {
        $this->adapter->set('foo', 'bar');

        $deleteResult = $this->adapter->delete('foo');

        $actual = 'bar';
        try {
            $actual = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual);
    }

    public function testIsDeletingMultipleKeys(): void
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $deleteResult = $this->adapter->deleteMulti(['foo', 'fooz']);

        $actual1 = 'bar';
        try {
            $actual1 = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $this->adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertNull($actual2);
    }

    public function testIsDeletingInexistentKey(): void
    {
        $actual = $this->adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingInexistentMultipleKeys(): void
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $deleteResult = $this->adapter->deleteMulti(['foo', 'nop']);

        $actual1 = 'bar';
        try {
            $actual1 = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $this->adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertEquals('baz', $actual2);
    }

    public function testIsFlushingData(): void
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $flushResult = $this->adapter->flush();

        $actual1 = 'bar';
        try {
            $actual1 = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $this->adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($flushResult);
        $this->assertNull($actual1);
        $this->assertNull($actual2);
    }

    protected function getMemcachedPersistentTestConfiguration()
    {
        return [
            'servers' => [
                ['127.0.0.1', 11211],
            ],
            'connection_persistent' => true,
            'pool_size' => 10,
        ];
    }

    protected function getInstanceProperty(object $instance, string $propertyName)
    {
        $reflection = new ReflectionClass($instance);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($instance);
    }
}
