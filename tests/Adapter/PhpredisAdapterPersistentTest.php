<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;
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
        $this->adapter = new PhpredisAdapter(['connection_persistent' => true], false);
        $this->namespace = 'mx';
        $this->adapter->setNamespace($this->namespace);
        $this->adapter->flush();
    }

    protected function tearDown()
    {
        /** @var $client \Redis */
        $client = PHPUnit_Framework_Assert::readAttribute($this->adapter, 'client');
        $client->close();
    }

    public function testIsCreatingPersistentConnection()
    {
        if (!$this->isThreadSafe()) {
            $this->markTestSkipped('Using thread safe version. Persistent connection is not supported when thread safe is enabled.');
        }

        $connection1 = new PhpredisAdapter(['connection_persistent' => true], false);
        $client1 = PHPUnit_Framework_Assert::readAttribute($connection1, 'client');
        /** @var $client1 \Redis */
        $info1 = $client1->info();
        $connectedClients1 = $info1['connected_clients'];

        $connection2 = new PhpredisAdapter(['connection_persistent' => true], false);
        $client2 = PHPUnit_Framework_Assert::readAttribute($connection2, 'client');
        /** @var $client2 \Redis */
        $info2 = $client2->info();
        $connectedClients2 = $info2['connected_clients'];

        $connection3 = new PhpredisAdapter(['connection_persistent' => false], false);
        $client3 = PHPUnit_Framework_Assert::readAttribute($connection3, 'client');
        /** @var $client3 \Redis */
        $info3 = $client3->info();
        $connectedClients3 = $info3['connected_clients'];

        $client1->close();
        $client2->close();
        $client3->close();

        $this->assertEquals(0, $connectedClients2 - $connectedClients1);
        $this->assertEquals(1, $connectedClients3 - $connectedClients2);
    }

    public function testIsRespectingPoolSize()
    {
        if (!$this->isThreadSafe()) {
            $this->markTestSkipped('Using thread safe version. Persistent connection is not supported when thread safe is enabled.');
        }

        $connections = [];
        for ($i = 1; $i <= 100; $i++) {
            $connection = new PhpredisAdapter(['connection_persistent' => true, 'pool_size' => 10], false);
            $connections[] = $connection;
        }

        $client100 = PHPUnit_Framework_Assert::readAttribute($connection, 'client');
        /** @var $client100 \Redis */
        $info = $client100->info();
        $connectedClients = $info['connected_clients'];

        $this->assertEquals(10, $connectedClients);
    }

    public function testIsSettingAndGetting()
    {
        $setResult = $this->adapter->set('foo', 'bar');
        $actual = $this->adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    /**
     * @expectedException \Linio\Component\Cache\Exception\KeyNotFoundException
     */
    public function testIsGettingInexistentKey()
    {
        $actual = $this->adapter->get('foo');
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

        $actual = 'bar';
        try {
            $actual = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual);
    }

    public function testIsDeletingMultipleKeys()
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $deleteResult = $this->adapter->deleteMulti(['foo', 'fooz']);

        $actual1 = 'bar';
        try {
            $actual1 = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $this->adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

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

        $actual1 = 'bar';
        try {
            $actual1 = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $this->adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($deleteResult);
        $this->assertNull($actual1);
        $this->assertEquals('baz', $actual2);
    }

    public function testIsFlushingData()
    {
        $this->adapter->set('foo', 'bar');
        $this->adapter->set('fooz', 'baz');

        $flushResult = $this->adapter->flush();

        $actual1 = 'bar';
        try {
            $actual1 = $this->adapter->get('foo');
        } catch (KeyNotFoundException $e) {
            $actual1 = null;
        }

        $actual2 = 'baz';
        try {
            $actual2 = $this->adapter->get('fooz');
        } catch (KeyNotFoundException $e) {
            $actual2 = null;
        }

        $this->assertTrue($flushResult);
        $this->assertNull($actual1);
        $this->assertNull($actual2);
    }

    protected function isThreadSafe()
    {
        ob_start();
        phpinfo(INFO_GENERAL);

        return preg_match('/Thread\s*Safety\s*enabled/i', strip_tags(ob_get_clean()));
    }
}
