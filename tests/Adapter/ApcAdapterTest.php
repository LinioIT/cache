<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;
use PHPUnit\Framework\TestCase;

class ApcAdapterTest extends TestCase
{
    protected ApcAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = $this->getMockBuilder('Linio\Component\Cache\Adapter\ApcAdapter')
            ->disableOriginalConstructor()
            ->setMethods(['setNamespace', 'set', 'get', 'delete', 'contains', 'flush', 'getMulti', 'setMulti', 'deleteMulti'])
            ->getMock();
        $this->adapter->setNamespace('mx');
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
}
