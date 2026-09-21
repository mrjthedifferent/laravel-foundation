<?php

namespace Mrj\Foundation\Services\Dashboard;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Shared TTL, versioning and invalidation for everything cached on the dashboard —
 * the persona payloads and the per-module widgets alike — so one flush clears the
 * lot and there is a single place that decides how long any of it lives.
 */
final readonly class DashboardCache
{
    /**
     * Counter used to invalidate every dashboard entry at once. The `database`
     * cache store does not support tags, so there is no Cache::tags()->flush().
     */
    private const string VERSION_KEY = 'dashboard:cache:version';

    /**
     * Caches the callback under a dashboard-scoped key, or runs it directly when
     * caching is switched off.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $key, Closure $callback): mixed
    {
        $ttl = $this->ttlSeconds();

        if ($ttl === 0) {
            return $callback();
        }

        return Cache::remember($this->qualify($key), $ttl, $callback);
    }

    /**
     * Prefixes a key with the payload version, so a rolled version orphans every
     * previously cached entry rather than needing them enumerated and deleted.
     */
    public function qualify(string $key): string
    {
        return 'dashboard:v1:'.$this->version().':'.$key;
    }

    public function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * Drops every cached dashboard entry by rolling the version counter.
     */
    public function flush(): void
    {
        // increment() is a no-op on a missing key in most stores, so seed it first.
        Cache::add(self::VERSION_KEY, 1);
        Cache::increment(self::VERSION_KEY);
    }

    /**
     * Zero means caching is disabled — a production kill-switch, and the clean way
     * to opt out in tests.
     */
    public function ttlSeconds(): int
    {
        $minutes = (int) config('settings.dashboard_cache_ttl_minutes.value', 10);

        return max(0, $minutes) * 60;
    }
}
