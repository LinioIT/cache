<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

interface EncoderInterface
{
    /**
     * @param $value
     *
     * @return string
     */
    public function encode($value): string;

    /**
     * @param $value
     *
     * @return mixed
     */
    public function decode($value);
}
