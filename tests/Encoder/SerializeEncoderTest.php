<?php

namespace Linio\Component\Cache\Encoder;

class SerializeEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEncoding()
    {
        $value = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $expected = 'a:2:{s:3:"foo";i:1;s:3:"bar";s:6:"string";}';

        $serializeEncoder = new SerializeEncoder();

        $actual = $serializeEncoder->encode($value);

        $this->assertEquals($expected, $actual);
    }

    public function testIsDecoding()
    {
        $value = 'a:2:{s:3:"foo";i:1;s:3:"bar";s:6:"string";}';

        $expected = [
            'foo' => 1,
            'bar' => 'string',
        ];

        $serializeEncoder = new SerializeEncoder();

        $actual = $serializeEncoder->decode($value);

        $this->assertEquals($expected, $actual);
    }
}
