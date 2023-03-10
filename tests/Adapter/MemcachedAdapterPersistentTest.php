<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MemcachedAdapterPersistentTest extends TestCase
{
    protected MemcachedAdapter $adapter;
    protected string $namespace;

    protected function setUp(): void
    {
        $this->adapter = $this->getMockBuilder(MemcachedAdapter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setNamespace', 'flush', 'set', 'get', 'delete', 'contains', 'getMulti', 'setMulti', 'deleteMulti'])
            ->getMock();
        $this->adapter->setNamespace('mx');

        $this->adapter->flush();
    }

    public function testIsCreatingPersistentConnection(): void
    {
        if (!$this->isThreadSafe()) {
            $this->markTestSkipped('Using thread safe version. Persistent connection is not supported when thread safe is enabled.');
        }

        /** @var $client \Memcached */
        $client = $this->getInstanceProperty($this->adapter, 'memcached');
        $this->assertTrue($client->isPersistent());
    }

    public function testIsRespectingPoolSize(): void
    {
        if (!$this->isThreadSafe()) {
            $this->markTestSkipped('Using thread safe version. Persistent connection is not supported when thread safe is enabled.');
        }

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
        $this->adapter->expects($this->once())
            ->method('set')
            ->with($this->equalTo('foo'))
            ->willReturn(true);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->willReturn('bar');

        $setResult = $this->adapter->set('foo', 'bar');
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    public function testIsGettingNonexistentKey(): void
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->willThrowException(new KeyNotFoundException());

        $this->expectException(KeyNotFoundException::class);

        $this->adapter->get('foo');
    }

    public function testIsFindingKey(): void
    {
        $this->adapter->expects($this->once())
            ->method('contains')
            ->with($this->equalTo('foo'))
            ->willReturn(true);

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
        $this->adapter->expects($this->once())
            ->method('getMulti')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(['foo' => 'bar', 'fooz' => 'baz']);

        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys(): void
    {
        $this->adapter->expects($this->once())
            ->method('getMulti')
            ->with($this->equalTo(['foo', 'nop']))
            ->willReturn(['foo' => 'bar']);

        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $actual = $this->adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingMultipleKeys(): void
    {
        $this->adapter->expects($this->once())
            ->method('setMulti')
            ->with($this->equalTo(['foo' => 'bar', 'fooz' => 'baz']))
            ->willReturn(true);

        $this->adapter->expects($this->once())
            ->method('getMulti')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(['bar', 'baz']);

        $actual = $this->adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
        $this->assertEquals(['bar', 'baz'], $this->adapter->getMulti(['foo', 'fooz']));
    }

    public function testIsDeletingKey(): void
    {
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('foo'))
            ->willReturn(true);

        $this->adapter->set('foo', 'bar');

        $deleteResult = $this->adapter->delete('foo');

        $actual = $this->adapter->get('foo');

        $this->assertTrue($deleteResult);
        $this->assertNull($actual);
    }

    public function testIsDeletingMultipleKeys(): void
    {
        $this->adapter->expects($this->once())
            ->method('deleteMulti')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(true);

        $this->adapter->expects($this->once())
            ->method('getMulti')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn([]);

        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $deleteResult = $this->adapter->deleteMulti(['foo', 'fooz']);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertTrue($deleteResult);
        $this->assertEmpty($actual);
    }

    public function testIsDeletingNonexistentKey(): void
    {
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('foo'))
            ->willReturn(true);

        $actual = $this->adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingNonexistentMultipleKeys(): void
    {
        $this->adapter->expects($this->once())
            ->method('deleteMulti')
            ->with($this->equalTo(['foo', 'nop']))
            ->willReturn(true);

        $this->adapter->method('get')
            ->withConsecutive(['foo'], ['fooz'])
            ->willReturnOnConsecutiveCalls(null, 'baz');

        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $deleteResult = $this->adapter->deleteMulti(['foo', 'nop']);

        $actual1 = $this->adapter->get('foo');
        $actual2 = $this->adapter->get('fooz');

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertEquals('baz', $actual2);
    }

    public function testIsFlushingData(): void
    {
        $this->adapter->expects($this->once())
            ->method('flush')
            ->willReturn(true);

        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $flushResult = $this->adapter->flush();

        $actual1 = $this->adapter->get('foo');

        $actual2 = $this->adapter->get('fooz');

        $this->assertTrue($flushResult);
        $this->assertNull($actual1);
        $this->assertNull($actual2);
    }

    protected function getInstanceProperty(object $instance, string $propertyName)
    {
        $reflection = new ReflectionClass($instance);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($instance);
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

    protected function isThreadSafe()
    {
        ob_start();
        phpinfo(INFO_GENERAL);

        return preg_match('/Thread\s*Safety\s*enabled/i', strip_tags(ob_get_clean()));
    }
}
