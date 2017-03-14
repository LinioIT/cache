<?php

namespace Linio\Component\Cache\Encoder;

class SerialEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEncoding()
    {
        $value = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $expected = 'a:2:{s:3:"foo";i:1;s:3:"bar";s:6:"string";}';

        $serialEncoder = new SerialEncoder();

        $actual = $serialEncoder->encode($value);

        $this->assertEquals($expected, $actual);
    }

    public function testIsDecoding()
    {
        $value = 'a:2:{s:3:"foo";i:1;s:3:"bar";s:6:"string";}';

        $expected = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $serialEncoder = new SerialEncoder();

        $actual = $serialEncoder->decode($value);

        $this->assertEquals($expected, $actual);
    }

    public function testIsDecodingBoolean()
    {
        $value = false;
        $expected = false;

        $serialEncoder = new SerialEncoder();
        $actual = $serialEncoder->decode($value);

        $this->assertEquals($expected, $actual);
    }

    public function testIsDecodingNull()
    {
        $value = null;
        $expected = null;

        $serialEncoder = new SerialEncoder();
        $actual = $serialEncoder->decode($value);

        $this->assertEquals($expected, $actual);
    }
}
