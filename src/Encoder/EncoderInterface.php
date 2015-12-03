<?php
declare(strict_types=1);

namespace Linio\Component\Cache\Encoder;

interface EncoderInterface
{
    public function encode($value): string;
    public function decode($value);
}
