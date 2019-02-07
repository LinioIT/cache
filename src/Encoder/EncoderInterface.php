<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

interface EncoderInterface
{
    /**
     * @param $value
     */
    public function encode($value): string;

    /**
     * @param $value
     */
    public function decode($value);
}
