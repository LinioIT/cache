<?php

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;

/**
 * @requires extension redis
 * @requires extension igbinary
 */
class PhpredisAdapterIgbinarySerializerTest extends \PHPUnit_Framework_TestCase
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
        try {
            $this->adapter = new PhpredisAdapter(['serializer' => 'igbinary']);
        } catch (InvalidConfigurationException $e) {
            $this->markTestSkipped('phpredis extension not compiled with igbinary support');
        }

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