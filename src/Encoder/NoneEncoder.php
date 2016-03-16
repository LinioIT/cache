<?php
declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

class NoneEncoder implements EncoderInterface
{
    /**
     * @param $value
     *
     * @return string
     */
    public function encode($value): string
    {
        return $value;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function decode($value)
    {
        return $value;
    }
}
