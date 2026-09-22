<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

/**
 * The single settings read path: every settings read goes through this
 * cached repository. Bound in SettingsServiceProvider.
 *
 * @api
 */
interface SettingsRepository
{
    /**
     * Every setting as a plain array, cached.
     *
     * @return list<array{key: string, value: mixed, group: ?string, type: ?string, description: ?string}>
     */
    public function all(): array;

    /**
     * A single setting's value, read from the cache.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * A single setting's value, read straight from the database — for the
     * few call sites (an SMS gateway send, a Firebase credential lookup)
     * where a value just changed in the same request/job and a stale cache
     * read is unacceptable.
     */
    public function fresh(string $key, mixed $default = null): mixed;

    public function forget(): void;
}
