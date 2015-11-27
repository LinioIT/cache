<?php
declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class SerialEncoder implements EncoderInterface
{
    public function encode($value): string
    {
        return serialize($value);
    }

    public function decode($value)
    {
        return unserialize($value);
    }
}
