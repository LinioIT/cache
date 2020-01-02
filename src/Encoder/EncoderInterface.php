<?php

declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

interface EncoderInterface
{
    /**
     * @param mixed $value
     */
    public function encode($value): string;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function decode($value);
}
