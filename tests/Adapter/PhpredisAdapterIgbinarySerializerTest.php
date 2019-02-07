<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Adapter;

use Linio\Component\Cache\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Assert;

/**
 * @requires extension redis
 * @requires extension igbinary
 */
class PhpredisAdapterIgbinarySerializerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PhpredisAdapter
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $namespace;

    protected function setUp(): void
    {
        try {
            $this->adapter = new PhpredisAdapter(['connection_persistent' => false, 'serializer' => 'igbinary']);
        } catch (InvalidConfigurationException $e) {
            $this->markTestSkipped('phpredis extension not compiled with igbinary support');
        }

        $this->namespace = 'mx';
        $this->adapter->setNamespace($this->namespace);
        $this->adapter->flush();
    }

    protected function tearDown(): void
    {
        /** @var $client \Redis */
        $client = Assert::readAttribute($this->adapter, 'client');
        $client->close();
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
