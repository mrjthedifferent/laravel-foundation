<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

/**
 * One change pushed by a client: create-or-update (`upsert`) or `delete`
 * of the row `id` in the sync collection `name`. `version` is the version
 * the client last saw (null for a row it created), used to detect conflicts.
 *
 * @api
 */
final readonly class SyncOperation
{
    public const string UPSERT = 'upsert';

    public const string DELETE = 'delete';

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $name,
        public string $op,
        public string $id,
        public ?int $version = null,
        public array $data = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            name: (string) $raw['name'],
            op: (string) $raw['op'],
            id: (string) $raw['id'],
            version: isset($raw['version']) ? (int) $raw['version'] : null,
            data: is_array($raw['data'] ?? null) ? $raw['data'] : [],
        );
    }

    public function isDelete(): bool
    {
        return $this->op === self::DELETE;
    }
}
