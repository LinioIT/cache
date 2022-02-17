<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\KeyNotFoundException;
use PHPUnit\Framework\TestCase;

class ArrayAdapterTest extends TestCase
{
    public const TEST_NAMESPACE = 'mx';

    public function testIsSettingAndGetting(): void
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $setResult = $adapter->set('foo', 'bar');
        $actual = $adapter->get('foo');

        $this->assertTrue($setResult);
        $this->assertEquals('bar', $actual);
    }

    public function testIsGettingNonexistentKey(): void
    {
        $this->expectException(\Linio\Component\Cache\Exception\KeyNotFoundException::class);

        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $actual = $adapter->get('foo');
    }

    public function testIsFindingKey(): void
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');

        $actual = $adapter->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsNotFindingKey(): void
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');

        $actual = $adapter->contains('baz');

        $this->assertFalse($actual);
    }

    public function testIsGettingMultipleKeys(): void
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');
        $adapter->set('fooz', 'baz');

        $actual = $adapter->getMulti(['foo', 'fooz']);

        $this->assertEquals(['foo' => 'bar', 'fooz' => 'baz'], $actual);
    }

    public function testIsGettingMultipleKeysWithInvalidKeys(): void
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);
        $adapter->set('foo', 'bar');
        $adapter->set('fooz', 'baz');

        $actual = $adapter->getMulti(['foo', 'nop']);

        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testIsSettingMultipleKeys(): void
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $actual = $adapter->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
        $this->assertEquals('bar', $adapter->get('foo'));
        $this->assertEquals('baz', $adapter->get('fooz'));
    }

    public function testIsDeletingKey(): void
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

    public function testIsDeletingMultipleKeys(): void
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

    public function testIsDeletingNonexistentKey(): void
    {
        $adapter = new ArrayAdapter();
        $adapter->setNamespace(static::TEST_NAMESPACE);

        $actual = $adapter->delete('foo');

        $this->assertTrue($actual);
    }

    public function testIsDeletingNonexistentMultipleKeys(): void
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

    public function testIsFlushingData(): void
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
