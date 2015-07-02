<?php

namespace Linio\Component\Cache\Encoder;

class SerializeEncoder implements EncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode($value)
    {
        return serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($value)
    {
        return unserialize($value);
    }
}
