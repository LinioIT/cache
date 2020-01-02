<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use PHPUnit\Framework\TestCase;
use Predis\Response\Status;

class RedisAdapterTest extends TestCase
{
    public function testIsSettingNamespace(): void
    {
        $clientMock = $this->prophesize('Predis\Client');
        $processorMock = $this->prophesize('Predis\Command\Processor\KeyPrefixProcessor');
        $optionsMock = $this->prophesize('Predis\Configuration\Options');
        $optionsMock->prefix = $processorMock;

        $redisAdapter = new RedisAdapter();
        $redisAdapter->setClient($clientMock->reveal());

        $clientMock->getOptions()
            ->willReturn($optionsMock->reveal());

        $processorMock->setPrefix('ns:')
            ->shouldBeCalled();

        $redisAdapter->setNamespace('ns');
    }

    public function testIsSettingAndGettingWithTtl(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get', 'set'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('set')
            ->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(RedisAdapter::EXPIRE_RESOLUTION_EX), 60)
            ->willReturn(new Status('OK'));

        $clientMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->willReturn('bar');

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');
        $adapter->setTtl(60);

        $setResult = $adapter->set('foo', 'bar');
        $actual = $adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    public function testIsSettingAndGettingWithoutTtl(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get', 'set'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo('foo'),
                $this->equalTo('bar')
            )
            ->willReturn(new Status('OK'));

        $clientMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->willReturn('bar');

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $setResult = $adapter->set('foo', 'bar');
        $actual = $adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    public function testIsGettingInexistentKey(): void
    {
        $this->expectException(\Linio\Component\Cache\Exception\KeyNotFoundException::class);

        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get', 'exists'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->willReturn(null);

        $clientMock->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('foo'))
            ->willReturn(false);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->get('foo');
    }

    public function testIsFindingKey(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('foo'))
            ->willReturn(true);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsNotFindingKey(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('baz'))
            ->willReturn(false);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->contains('baz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['mget'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('mget')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(['bar', 'baz']);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['mget'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('mget')
            ->with($this->equalTo(['foo', 'nop']))
            ->willReturn(['bar', null]);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingMultipleKeys(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['pipeline'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('pipeline')
            ->with($this->anything())
            ->will($this->returnValue([new Status('OK'), new Status('OK')]));

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz'], 10);

        $this->assertTrue($actual);
    }

    public function testIsDeletingKey(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('del')
            ->with($this->equalTo(['foo']))
            ->willReturn(true);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingMultipleKeys(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('del')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(true);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->deleteMulti(['foo', 'fooz']);

        $this->assertTrue($actual);
    }

    public function testIsDeletingInexistentKey(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('del')
            ->with($this->equalTo(['foo']))
            ->willReturn(true);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingInexistentMultipleKeys(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('del')
            ->with($this->equalTo(['foo', 'nop']))
            ->willReturn(true);

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $actual = $adapter->deleteMulti(['foo', 'nop']);

        $this->assertTrue($actual);
    }

    public function testIsFlushingData(): void
    {
        $clientMock = $this->getMockBuilder('\Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['flushAll'])
            ->getMock();

        $clientMock->expects($this->once())
            ->method('flushAll')
            ->willReturn(new Status('OK'));

        $adapter = $this->getRedisAdapterMock();
        $adapter->setClient($clientMock);
        $adapter->setNamespace('mx');

        $flushResult = $adapter->flush();

        $this->assertTrue($flushResult);
    }

    /**
     * @return RedisAdapter
     */
    private function getRedisAdapterMock()
    {
        return $this->getMockBuilder('Linio\Component\Cache\Adapter\RedisAdapter')
            ->disableOriginalConstructor()
            ->setMethods(['setNamespace'])
            ->getMock();
    }
}
