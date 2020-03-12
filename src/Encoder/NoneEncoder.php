<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class NoneEncoder implements EncoderInterface
{
    /**
     * @param mixed $value
     */
    public function encode($value): string
    {
        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function decode($value)
    {
        return $value;
    }
}
