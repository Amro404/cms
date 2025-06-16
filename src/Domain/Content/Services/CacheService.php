<?php

namespace Src\Domain\Content\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const CONTENT_CACHE_PREFIX = 'content:';
    private const CONTENT_LIST_CACHE_PREFIX = 'content_list:';
    private const CACHE_TTL = 3600;

    public function getCachedContent(int|string $id): mixed
    {
        return Cache::tags(['content'])->get(self::CONTENT_CACHE_PREFIX . $id);
    }

    public function cacheContent(int|string  $id, $content): void
    {
        Cache::tags(['content'])->put(
            self::CONTENT_CACHE_PREFIX . $id, 
            $content, 
            self::CACHE_TTL
        );
    }

    public function getCachedContentList(string $key): mixed
    {
        return Cache::tags(['content', 'content_list'])->get(self::CONTENT_LIST_CACHE_PREFIX . $key);
    }

    public function cacheContentList(string $key, $data): void
    {
        Cache::tags(['content', 'content_list'])->put(
            self::CONTENT_LIST_CACHE_PREFIX . $key,
            $data,
            self::CACHE_TTL
        );
    }

    public function invalidateContentCache(?int $id = null): void
    {
        if ($id !== null) {
            Cache::tags(['content'])->forget(self::CONTENT_CACHE_PREFIX . $id);
        }
        
        Cache::tags(['content_list'])->flush();
    }
}