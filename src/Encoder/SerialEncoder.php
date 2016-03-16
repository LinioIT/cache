<?php
declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class SerialEncoder implements EncoderInterface
{
    /**
     * @param $value
     *
     * @return string
     */
    public function encode($value): string
    {
        return serialize($value);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function decode($value)
    {
        return unserialize($value);
    }
}
