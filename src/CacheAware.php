<?php

declare(strict_types=1);

namespace Linio\Component\Cache;

trait CacheAware
{
    protected CacheService $cacheService;

    public function getCacheService(): CacheService
    {
        return $this->cacheService;
    }

    public function setCacheService(CacheService $cacheService): void
    {
        $this->cacheService = $cacheService;
    }
}
