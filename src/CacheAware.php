<?php

namespace Linio\Component\Cache;

trait CacheAware
{
    /**
     * @var \Linio\Component\Cache\CacheService
     */
    protected $cacheService;

    /**
     * @return \Linio\Component\Cache\CacheService
     */
    public function getCacheService()
    {
        return $this->cacheService;
    }

    /**
     * @param \Linio\Component\Cache\CacheService $cacheService
     */
    public function setCacheService(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
}
