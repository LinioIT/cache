<?php

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;

class ArrayAdapterTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAMESPACE = 'mx';

    public function testIsSettingAndGetting()
    {

        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $setResult = $adapter->set('foo', 'bar');
        $actual = $adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    /**
     * @expectedException \Linio\Component\Cache\Exception\KeyNotFoundException
     */
    public function testIsGettingInexistentKey()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $actual = $adapter->get('foo');
    }

    public function testIsFindingKey()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');

        $actual = $adapter->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsNotFindingKey()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');

        $actual = $adapter->contains('baz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');
        $adapter->set('fooz', 'baz');

        $actual = $adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');
        $adapter->set('fooz', 'baz');

        $actual = $adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingMultipleKeys()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $actual = $adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
        $this->assertEquals('bar', $adapter->get('foo'));
        $this->assertEquals('baz', $adapter->get('fooz'));
    }

    public function testIsDeletingKey()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');

        $deleteResult = $adapter->delete('foo');

        $actual = 'bar';
        try {
            $actual = $adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual);
    }

    public function testIsDeletingMultipleKeys()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');
        $adapter->set('fooz', 'baz');

        $deleteResult = $adapter->deleteMulti(['foo', 'fooz']);

        $actual1 = 'bar';
        try {
            $actual1 = $adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertNull($actual2);
    }

    public function testIsDeletingInexistentKey()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $actual = $adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingInexistentMultipleKeys()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');
        $adapter->set('fooz', 'baz');

        $deleteResult = $adapter->deleteMulti(['foo', 'nop']);

        $actual1 = 'bar';
        try {
            $actual1 = $adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertEquals('baz', $actual2);
    }

    public function testIsFlushingData()
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');
        $adapter->set('fooz', 'baz');

        $flushResult = $adapter->flush();

        $actual1 = 'bar';
        try {
            $actual1 = $adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($flushResult);
        $this->assertNull($actual1);
        $this->assertNull($actual2);
    }
}
