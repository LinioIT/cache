<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use PHPUnit\Framework\TestCase;
use stdClass;

class PhpredisAdapterIgbinarySerializerTest extends TestCase
{
    protected PhpredisAdapter $adapter;
    protected string $namespace;

    protected function setUp(): void
    {
        $this->adapter = $this->getMockBuilder(PhpredisAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['setNamespace', 'set', 'get', 'delete', 'contains', 'flush', 'getMulti', 'setMulti', 'deleteMulti'])
            ->getMock();
        $this->adapter->setNamespace('mx');
    }

    public function testIsSettingAndGettingArray(): void
    {
        $this->adapter->expects($this->once())
            ->method('set')
            ->with($this->equalTo('foo'))
            ->willReturn(true);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->willReturn(['bar']);

        $setResult = $this->adapter->set('foo', ['bar']);
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals(['bar'], $actual);
    }

    public function testIsSettingAndGettingObject(): void
    {
        $bar = new stdClass();
        $bar->bar = 'bar';

        $this->adapter->expects($this->once())
            ->method('set')
            ->with($this->equalTo('foo'))
            ->willReturn(true);

        $this->adapter->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->willReturn($bar);

        $setResult = $this->adapter->set('foo', $bar);
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals($bar, $actual);
    }

    public function testIsGettingMultipleKeysWithArrayValues(): void
    {
        $this->adapter->expects($this->once())
            ->method('getMulti')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(['foo' => ['bar'], 'fooz' => ['baz']]);

        $this->adapter->set('foo', ['bar']);
        $this->adapter->set('fooz', ['baz']);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => ['bar'], 'fooz' => ['baz']], $actual);
    }

    public function testIsGettingMultipleKeysWithObjectValues(): void
    {
        $bar = new stdClass();
        $bar->bar = 'bar';

        $baz = new stdClass();
        $baz->baz = 'baz';

        $this->adapter->expects($this->once())
            ->method('getMulti')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(['foo' => $bar, 'fooz' => $baz]);

        $this->adapter->set('foo', $bar);
        $this->adapter->set('fooz', $baz);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => $bar, 'fooz' => $baz], $actual);
    }

    public function testIsSettingMultipleKeys(): void
    {
        $bar = new stdClass();
        $bar->bar = 'bar';

        $baz = new stdClass();
        $baz->baz = 'baz';

        $this->adapter->expects($this->once())
            ->method('setMulti')
            ->with($this->equalTo(['foo', 'fooz']))
            ->willReturn(true);

        $this->adapter->method('get')
            ->withConsecutive(['foo'], ['fooz'])
            ->willReturnOnConsecutiveCalls($bar, $baz);

        $actual = $this->adapter->setMulti(['foo', 'fooz']);

        $this->assertTrue($actual);
        $this->assertEquals($bar, $this->adapter->get('foo'));
        $this->assertEquals($baz, $this->adapter->get('fooz'));
    }
}
