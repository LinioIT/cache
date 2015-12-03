<?php
declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

use Linio\Component\Util\Json;

class JsonEncoder implements EncoderInterface
{
    public function encode($value): string
    {
        return Json::encode($value);
    }

    public function decode($value)
    {
        return Json::decode($value);
    }
}
