<?php

namespace App\Support;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

/**
 * Safe cache wrapper that falls back gracefully when the configured cache driver
 * does not support tagging (e.g. file, database). Redis and Memcached support tags;
 * the file/database/array drivers do not.
 */
class TaggableCache
{
    /**
     * Retrieve or store a cached value, using tags when supported.
     */
    public static function remember(array $tags, string $key, int $ttl, callable $callback): mixed
    {
        if (self::supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Remove a specific cached key, honoring tags when supported.
     */
    public static function forget(array $tags, string $key): void
    {
        if (self::supportsTags()) {
            Cache::tags($tags)->forget($key);
        } else {
            Cache::forget($key);
        }
    }

    /**
     * Flush all entries for the given tags when supported.
     * Falls back to a no-op on non-taggable drivers — callers should use
     * forget() with explicit keys when tag-flush is unavailable.
     */
    public static function flush(array $tags): void
    {
        if (self::supportsTags()) {
            Cache::tags($tags)->flush();
        }
        // Non-taggable drivers: silently skip — individual forget() calls
        // in model observers handle per-record invalidation.
    }

    /**
     * Whether the currently configured cache store supports tagging.
     */
    public static function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }
}
