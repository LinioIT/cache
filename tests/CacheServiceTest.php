<?php

declare(strict_types=1);

namespace Linio\Component\Cache;

class CacheServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $layer1CacheAdapterName;

    public function __construct()
    {
        $this->layer1CacheAdapterName = (extension_loaded('apcu')) ? 'apcu' : 'wincache';
    }

    protected function setUp()
    {
        if (extension_loaded('apcu')) {
            apcu_clear_cache();
        } elseif (extension_loaded('wincache')) {
            wincache_ucache_clear();
        } else {
            $this->markTestSkipped('missing cache extension: apcu |" wincache');
        }
    }

    public function testIsConstructingService()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $this->assertInstanceOf('\Linio\Component\Cache\CacheService', $cacheService);
    }

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     */
    public function testIsValidatingServiceConfiguration()
    {
        new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
            ]
        );
    }

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Adapter class does not exist: Linio\Component\Cache\Adapter\NopAdapter
     */
    public function testIsValidatingAdapters()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'nop',
                        'adapter_options' => [],
                    ],
                ],
            ]
        );

        $cacheService->get('test');
    }

    /**
     * @expectedException \Linio\Component\Cache\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Encoder class does not exist: Linio\Component\Cache\Encoder\NopEncoder
     */
    public function testIsValidatingEncoder()
    {
        new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'encoder' => 'nop',
                'layers' => [
                    0 => [
                        'adapter_name' => 'apcu',
                        'adapter_options' => [],
                    ],
                ],
            ]
        );
    }

    public function testIsGettingKey()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', json_encode('bar'));
        $adapterStack[1]->set('foo', json_encode('bar'));

        $actual = $cacheService->get('foo');

        $this->assertEquals('bar', $actual);
    }

    public function testIsGettingKeyRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[1]->set('foo', json_encode('bar'));

        $actual = $cacheService->get('foo');

        $this->assertEquals('bar', $actual);
        $this->assertEquals(json_encode('bar'), $adapterStack[0]->get('foo'));
    }

    public function testIsGettingMissingKey()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', json_encode('bar'));
        $adapterStack[1]->set('foo', json_encode('bar'));

        $actual = $cacheService->get('nop');

        $this->assertNull($actual);
    }

    public function testIsCachingGettingMissingKey()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [
                            'cache_not_found_keys' => true,
                        ],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', json_encode('bar'));
        $adapterStack[1]->set('foo', json_encode('bar'));

        $actual = $cacheService->get('nop');

        $this->assertNull($actual);

        $this->assertTrue($adapterStack[0]->contains('nop'));
    }

    public function testIsGettingKeyMultipleKeys()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', json_encode('bar'));
        $adapterStack[0]->set('fooz', json_encode('baz'));
        $adapterStack[1]->set('foo', json_encode('bar'));
        $adapterStack[1]->set('fooz', json_encode('baz'));

        $actual = $cacheService->getMulti(['foo', 'fooz']);

        $this->assertInternalType('array', $actual);
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['fooz']);
    }

    public function testIsGettingKeyMultipleKeysRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[1]->set('foo', json_encode('bar'));
        $adapterStack[1]->set('fooz', json_encode('baz'));

        $actual = $cacheService->getMulti(['foo', 'fooz']);

        $this->assertInternalType('array', $actual);
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['fooz']);
    }

    public function testIsGettingKeyMultipleKeysRecursivelyWithComplimentaryLevelData()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', json_encode('bar'));
        $adapterStack[1]->set('fooz', json_encode('baz'));

        $actual = $cacheService->getMulti(['foo', 'fooz']);

        $this->assertInternalType('array', $actual);
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['fooz']);
    }

    public function testIsGettingKeyMultipleKeysRecursivelyWithMissingKeys()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', json_encode('bar'));
        $adapterStack[1]->set('fooz', json_encode('baz'));

        $actual = $cacheService->getMulti(['foo', 'nop']);

        $this->assertInternalType('array', $actual);
        $this->assertEquals('bar', $actual['foo']);
        $this->assertArrayNotHasKey('nop', $actual);
    }

    public function testIsSettingKeyRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $actual = $cacheService->set('foo', 'bar');

        $this->assertTrue($actual);
        $adapterStack = $cacheService->getAdapterStack();
        $this->assertEquals(json_encode('bar'), $adapterStack[0]->get('foo'));
        $this->assertEquals(json_encode('bar'), $adapterStack[1]->get('foo'));
    }

    public function testIsSettingMultipleKeysRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $actual = $cacheService->setMulti(['foo' => 'bar', 'fooz' => 'baz']);

        $this->assertTrue($actual);
        $adapterStack = $cacheService->getAdapterStack();
        $this->assertEquals(json_encode('bar'), $adapterStack[0]->get('foo'));
        $this->assertEquals(json_encode('bar'), $adapterStack[1]->get('foo'));
        $this->assertEquals(json_encode('baz'), $adapterStack[0]->get('fooz'));
        $this->assertEquals(json_encode('baz'), $adapterStack[1]->get('fooz'));
    }

    public function testIsCheckingContainsKey()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', 'bar');
        $adapterStack[1]->set('foo', 'bar');

        $actual = $cacheService->contains('foo');

        $this->assertTrue($actual);
    }

    public function testIsCheckingContainsKeyRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[1]->set('foo', 'bar');

        $actual = $cacheService->contains('foo');

        $this->assertTrue($actual);
        $this->assertFalse($adapterStack[0]->contains('foo'));
    }

    public function testIsCheckingNotContainsKey()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', 'bar');
        $adapterStack[1]->set('foo', 'bar');

        $actual = $cacheService->contains('fooz');

        $this->assertFalse($actual);
    }

    public function testIsDeletingKeyRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', 'bar');
        $adapterStack[1]->set('foo', 'bar');

        $actual = $cacheService->delete('foo');

        $this->assertTrue($actual);
        $this->assertFalse($adapterStack[0]->contains('foo'));
        $this->assertFalse($adapterStack[1]->contains('foo'));
    }

    public function testIsDeletingMissingKeyRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', 'bar');
        $adapterStack[1]->set('foo', 'bar');

        $actual = $cacheService->delete('nop');

        $this->assertTrue($actual);
        $this->assertTrue($adapterStack[0]->contains('foo'));
        $this->assertTrue($adapterStack[1]->contains('foo'));
    }

    public function testIsDeletingMultipleKeysRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', 'bar');
        $adapterStack[1]->set('foo', 'bar');
        $adapterStack[0]->set('fooz', 'baz');
        $adapterStack[1]->set('fooz', 'baz');

        $actual = $cacheService->deleteMulti(['foo', 'fooz']);

        $this->assertTrue($actual);
        $this->assertFalse($adapterStack[0]->contains('foo'));
        $this->assertFalse($adapterStack[1]->contains('foo'));
        $this->assertFalse($adapterStack[0]->contains('fooz'));
        $this->assertFalse($adapterStack[1]->contains('fooz'));
    }

    public function testIsDeletingMultipleMissingKeysRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', 'bar');
        $adapterStack[1]->set('foo', 'bar');
        $adapterStack[0]->set('fooz', 'baz');
        $adapterStack[1]->set('fooz', 'baz');

        $actual = $cacheService->deleteMulti(['nop', 'noz']);

        $this->assertTrue($actual);
        $this->assertTrue($adapterStack[0]->contains('foo'));
        $this->assertTrue($adapterStack[1]->contains('foo'));
        $this->assertTrue($adapterStack[0]->contains('fooz'));
        $this->assertTrue($adapterStack[1]->contains('fooz'));
    }

    public function testIsFlushingRecursively()
    {
        $cacheService = new CacheService(
            [
                'namespace' => 'mx',
                'encoder' => 'json',
                'layers' => [
                    0 => [
                        'adapter_name' => 'array',
                        'adapter_options' => [],
                    ],
                    1 => [
                        'adapter_name' => $this->layer1CacheAdapterName,
                        'adapter_options' => [
                            'ttl' => 3600,
                        ],
                    ],
                ],
            ]
        );

        $adapterStack = $cacheService->getAdapterStack();
        $adapterStack[0]->set('foo', 'bar');
        $adapterStack[1]->set('foo', 'bar');
        $adapterStack[0]->set('fooz', 'baz');
        $adapterStack[1]->set('fooz', 'baz');

        $actual = $cacheService->flush();

        $this->assertTrue($actual);
        $this->assertFalse($adapterStack[0]->contains('foo'));
        $this->assertFalse($adapterStack[1]->contains('foo'));
        $this->assertFalse($adapterStack[0]->contains('fooz'));
        $this->assertFalse($adapterStack[1]->contains('fooz'));
    }
}
