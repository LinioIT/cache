<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class JsonEncoderTest extends \PHPUnit\Framework\TestCase
{
    public function testIsEncoding(): void
    {
        $value = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $expected = '{"foo":1,"bar":"string"}';

        $jsonEncoder = new JsonEncoder();

        $actual = $jsonEncoder->encode($value);

        $this->assertSame($expected, $actual);
    }

    public function testIsDecoding(): void
    {
        $value = '{"foo":1,"bar":"string"}';

        $expected = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $jsonEncoder = new JsonEncoder();

        $actual = $jsonEncoder->decode($value);

        $this->assertSame($expected, $actual);
    }

    public function testIsDecodingBoolean(): void
    {
        $value = false;
        $expected = false;

        $serialEncoder = new JsonEncoder();
        $actual = $serialEncoder->decode($value);

        $this->assertSame($expected, $actual);
    }

    public function testIsDecodingNull(): void
    {
        $value = null;
        $expected = null;

        $serialEncoder = new JsonEncoder();
        $actual = $serialEncoder->decode($value);

        $this->assertSame($expected, $actual);
    }
}
