<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

/**
 * The outcome of one pushed operation.
 *
 * - `applied`: saved; `version` is the row's new version.
 * - `conflict`: the server copy changed since the client's `version`;
 *   `server` holds the current row (null when it was deleted) for the client
 *   to adopt or re-apply its change on top of.
 * - `rejected`: refused (validation, permission, unknown collection); the
 *   client should surface `errors` and not retry unchanged.
 *
 * @api
 */
final readonly class SyncResult
{
    public const string APPLIED = 'applied';

    public const string CONFLICT = 'conflict';

    public const string REJECTED = 'rejected';

    /**
     * @param  array<string, mixed>|null  $server
     * @param  array<string, list<string>>  $errors
     */
    private function __construct(
        public string $status,
        public string $name,
        public string $id,
        public ?int $version = null,
        public ?array $server = null,
        public array $errors = [],
    ) {}

    public static function applied(SyncOperation $op, int $version): self
    {
        return new self(self::APPLIED, $op->name, $op->id, $version);
    }

    /**
     * @param  array<string, mixed>|null  $server
     */
    public static function conflict(SyncOperation $op, ?array $server): self
    {
        return new self(self::CONFLICT, $op->name, $op->id, isset($server['version']) ? (int) $server['version'] : null, $server);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    public static function rejected(SyncOperation $op, array $errors): self
    {
        return new self(self::REJECTED, $op->name, $op->id, null, null, $errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = ['name' => $this->name, 'id' => $this->id, 'status' => $this->status];

        if ($this->version !== null) {
            $result['version'] = $this->version;
        }

        if ($this->status === self::CONFLICT) {
            // Null means the row was deleted on the server.
            $result['server'] = $this->server;
        }

        if ($this->errors !== []) {
            $result['errors'] = $this->errors;
        }

        return $result;
    }
}
