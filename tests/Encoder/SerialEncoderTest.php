<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

use PHPUnit\Framework\TestCase;

class SerialEncoderTest extends TestCase
{
    public function testIsEncoding(): void
    {
        $value = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $expected = 'a:2:{s:3:"foo";i:1;s:3:"bar";s:6:"string";}';

        $serialEncoder = new SerialEncoder();

        $actual = $serialEncoder->encode($value);

        $this->assertSame($expected, $actual);
    }

    public function testIsDecoding(): void
    {
        $value = 'a:2:{s:3:"foo";i:1;s:3:"bar";s:6:"string";}';

        $expected = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $serialEncoder = new SerialEncoder();

        $actual = $serialEncoder->decode($value);

        $this->assertSame($expected, $actual);
    }

    public function testIsDecodingBoolean(): void
    {
        $value = false;
        $expected = false;

        $serialEncoder = new SerialEncoder();
        $actual = $serialEncoder->decode($value);

        $this->assertSame($expected, $actual);
    }

    public function testIsDecodingNull(): void
    {
        $value = null;
        $expected = null;

        $serialEncoder = new SerialEncoder();
        $actual = $serialEncoder->decode($value);

        $this->assertSame($expected, $actual);
    }
}
