<?php

declare(strict_types=1);

namespace Linio\Component\Cache;

trait CacheAware
{
    /**
     * @var CacheService
     */
    protected $cacheService;

    public function getCacheService(): CacheService
    {
        return $this->cacheService;
    }

    public function setCacheService(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
}
