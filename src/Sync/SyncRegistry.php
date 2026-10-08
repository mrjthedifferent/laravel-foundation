<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

use InvalidArgumentException;
use Mrj\Foundation\Contracts\SyncHandler;

/**
 * Resolves the collections listed in `foundation.offline_sync.handlers`.
 *
 * @api
 */
final class SyncRegistry
{
    public static function enabled(): bool
    {
        return self::names() !== [];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys((array) config('foundation.offline_sync.handlers', []));
    }

    public static function has(string $name): bool
    {
        return array_key_exists($name, (array) config('foundation.offline_sync.handlers', []));
    }

    public static function handler(string $name): SyncHandler
    {
        $class = config('foundation.offline_sync.handlers.'.$name);
        $handler = is_string($class) ? app($class) : null;

        if (! $handler instanceof SyncHandler) {
            throw new InvalidArgumentException("No sync handler is registered for [{$name}].");
        }

        return $handler;
    }
}
