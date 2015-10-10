<?php

namespace Linio\Component\Cache\Adapter;

use PHPUnit_Framework_Assert;

/**
 * @requires extension redis
 */
class PhpredisAdapterPersistentTest extends \PHPUnit_Framework_TestCase
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
        $this->adapter = new PhpredisAdapter(['connection_persistent' => true]);
        $this->namespace = 'mx';
        $this->adapter->setNamespace($this->namespace);
        $this->adapter->flush();
    }

    public function testIsCreatingPersistentConnection()
    {
        $connection1 = new PhpredisAdapter(['connection_persistent' => true]);
        $client1 = PHPUnit_Framework_Assert::readAttribute($connection1, 'client');
        /* @var $client1 \Redis */
        $info1 = $client1->info();
        $connected1 = $info1['connected_clients'];

        $connection2 = new PhpredisAdapter(['connection_persistent' => true]);
        $client2 = PHPUnit_Framework_Assert::readAttribute($connection2, 'client');
        /* @var $client2 \Redis */
        $info2 = $client2->info();
        $connected2 = $info2['connected_clients'];

        $connection3 = new PhpredisAdapter(['connection_persistent' => false]);
        $client3 = PHPUnit_Framework_Assert::readAttribute($connection3, 'client');
        /* @var $client3 \Redis */
        $info3 = $client3->info();
        $connected3 = $info3['connected_clients'];

        $client1->close();
        $client2->close();
        $client3->close();

        $this->assertEquals(0, $connected2 - $connected1);
        $this->assertEquals(1, $connected3 - $connected2);
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
        $this->adapter->set('foo', 'bar');

        $actual = $this->adapter->contains('baz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys()
    {
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
