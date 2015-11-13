<?php

namespace Linio\Component\Cache\Encoder;

class NoneEncoder implements EncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($value)
    {
        return $value;
    }
}
