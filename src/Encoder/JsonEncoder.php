<?php

namespace Linio\Component\Cache\Encoder;

use Linio\Component\Util\Json;

class JsonEncoder implements EncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode($value)
    {
        return Json::encode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($value)
    {
        return Json::decode($value);
    }
}
