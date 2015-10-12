<?php

namespace Linio\Component\Cache\Adapter;

/**
 * @requires extension redis
 */
class PhpredisAdapterPhpSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PhpredisAdapter
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $namespace;

    protected function setUp()
    {
        $this->adapter = new PhpredisAdapter(['serializer' => 'php']);
        $this->namespace = 'mx';
        $this->adapter->setNamespace($this->namespace);
        $this->adapter->flush();
    }

    public function testIsSettingAndGettingArray()
    {
        $setResult = $this->adapter->set('foo', ['bar']);
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals(['bar'], $actual);
    }

    public function testIsSettingAndGettingObject()
    {
        $bar = new \StdClass();
        $bar->bar = 'bar';

        $setResult = $this->adapter->set('foo', $bar);
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals($bar, $actual);
    }

    public function testIsGettingMultipleKeysWithArrayValues()
    {
        $this->adapter->set('foo', ['bar']);
        $this->adapter->set('fooz', ['baz']);

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => ['bar'], 'fooz' => ['baz']], $actual);
    }

    public function testIsGettingMultipleKeysWithObjectValues()
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

    public function testIsSettingMultipleKeys()
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