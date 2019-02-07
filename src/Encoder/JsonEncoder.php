<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

use Linio\Component\Util\Json;

class JsonEncoder implements EncoderInterface
{
    /**
     * @param $value
     */
    public function encode($value): string
    {
        return Json::encode($value);
    }

    /**
     * @param $value
     */
    public function decode($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return Json::decode($value);
    }
}
