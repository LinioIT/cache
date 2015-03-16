<?php

namespace Linio\Component\Cache\Adapter;

/**
 * @requires extension aerospike
 */
class AerospikeAdapterTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAMESPACE = 'mx';

    /**
     * @var AerospikeAdapter
     */
    protected $adapter;

    protected function setUp()
    {
        $this->adapter = new AerospikeAdapter(['hosts' => [['addr' => '127.0.0.1', 'port' => 3000]], 'persistent' => true]);
        $this->adapter->setNamespace(static::TEST_NAMESPACE);
        $this->adapter->flush();
    }

    public function testIsSettingAndGetting()
    {
        $setResult = $this->adapter->set('foo', 'bar');
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    public function testIsGettingInexistentKey()
    {
        $actual = $this->adapter->get('foo');

        $this->assertNull($actual);
    }

    public function testIsFindingKey()
    {
        $this->adapter->set('foo', 'bar');

        $actual = $this->adapter->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsNotFindingKey()
    {
        $this->adapter->setNamespace(static::TEST_NAMESPACE);
        $this->adapter->set('foo', 'bar');

        $actual = $this->adapter->contains('fooz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys()
    {
        $this->adapter->setNamespace(static::TEST_NAMESPACE);
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $actual = $this->adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys()
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $actual = $this->adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingMultipleKeys()
    {
        $actual = $this->adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
        $this->assertEquals('bar', $this->adapter->get('foo'));
        $this->assertEquals('baz', $this->adapter->get('fooz'));
    }

    public function testIsDeletingKey()
    {
        $this->adapter->set('foo', 'bar');

        $deleteResult = $this->adapter->delete('foo');
        $actual = $this->adapter->get('foo');

        $this->assertTrue($deleteResult);
        $this->assertNull($actual);
    }

    public function testIsDeletingMultipleKeys()
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $deleteResult = $this->adapter->deleteMulti(['foo', 'fooz']);
        $actual1 = $this->adapter->get('foo');
        $actual2 = $this->adapter->get('fooz');

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertNull($actual2);
    }

    public function testIsDeletingInexistentKey()
    {
        $actual = $this->adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingInexistentMultipleKeys()
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $deleteResult = $this->adapter->deleteMulti(['foo', 'nop']);
        $actual1 = $this->adapter->get('foo');
        $actual2 = $this->adapter->get('fooz');

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertEquals('baz', $actual2);
    }

    public function testIsFlushingData()
    {
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
