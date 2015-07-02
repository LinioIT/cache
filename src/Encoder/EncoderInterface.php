<?php

namespace Linio\Component\Cache\Encoder;

interface EncoderInterface
{
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function encode($value);

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function decode($value);
}
