<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use PHPUnit\Framework\TestCase;

/**
 * @requires extension redis
 */
class PhpredisAdapterPhpSerializerTest extends TestCase
{
    protected PhpredisAdapter $adapter;
    protected string $namespace;

    protected function setUp(): void
    {
        $this->adapter = new PhpredisAdapter(['serializer' => 'php']);
        $this->namespace = 'mx';
        $this->adapter->setNamespace($this->namespace);
        $this->adapter->flush();
    }

    public function testIsSettingAndGettingArray(): void
    {
        $setResult = $this->adapter->set('foo', ['bar']);
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals(['bar'], $actual);
    }

    public function testIsSettingAndGettingObject(): void
    {
        $bar = new \StdClass();
        $bar->bar = 'bar';

        $setResult = $this->adapter->set('foo', $bar);
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals($bar, $actual);
    }

    public function testIsGettingMultipleKeysWithArrayValues(): void
    {
        $this->adapter->set('foo', ['bar']);
        $this->adapter->set('fooz', ['baz']);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => ['bar'], 'fooz' => ['baz']], $actual);
    }

    public function testIsGettingMultipleKeysWithObjectValues(): void
    {
        $bar = new \StdClass();
        $bar->bar = 'bar';

        $baz = new \StdClass();
        $baz->baz = 'baz';

        $this->adapter->set('foo', $bar);
        $this->adapter->set('fooz', $baz);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => $bar, 'fooz' => $baz], $actual);
    }

    public function testIsSettingMultipleKeys(): void
    {
        $bar = new \StdClass();
        $bar->bar = 'bar';

        $baz = new \StdClass();
        $baz->baz = 'baz';

        $actual = $this->adapter->setMulti(['foo' => $bar, 'fooz' => $baz]);

        $this->assertTrue($actual);
        $this->assertEquals($bar, $this->adapter->get('foo'));
        $this->assertEquals($baz, $this->adapter->get('fooz'));
    }
}
