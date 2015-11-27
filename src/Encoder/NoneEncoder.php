<?php
declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class NoneEncoder implements EncoderInterface
{
    public function encode($value): string
    {
        return $value;
    }

    public function decode($value)
    {
        return $value;
    }
}
