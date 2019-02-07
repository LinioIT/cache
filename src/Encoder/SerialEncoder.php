<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class SerialEncoder implements EncoderInterface
{
    /**
     * @param $value
     */
    public function encode($value): string
    {
        return serialize($value);
    }

    /**
     * @param $value
     */
    public function decode($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return unserialize($value);
    }
}
