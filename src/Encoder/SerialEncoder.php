<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class SerialEncoder implements EncoderInterface
{
    /**
     * @param mixed $value
     */
    public function encode($value): string
    {
        return serialize($value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function decode($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return unserialize($value);
    }
}
