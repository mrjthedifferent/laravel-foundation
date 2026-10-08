<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A model that offline clients keep a copy of. It gets:
 *
 * - a ULID primary key, which the client generates so rows can be created
 *   offline and referenced before they reach the server;
 * - soft deletes, so a deletion reaches other devices as a tombstone;
 * - a `version` counter, bumped on every change, for conflict detection.
 *
 * The table needs `$table->ulid('id')->primary();`, `$table->timestamps();` and
 * `$table->syncable();` (version, deleted_at and the pull index).
 *
 * @mixin Model
 *
 * @api
 */
trait Syncable
{
    use HasUlids;
    use SoftDeletes;

    public static function bootSyncable(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('version') === null) {
                $model->setAttribute('version', 1);
            }
        });

        static::updating(function (Model $model): void {
            if (! $model->isDirty('version')) {
                $model->setAttribute('version', (int) $model->getOriginal('version') + 1);
            }
        });

        // A soft delete writes deleted_at with a direct query and fires no
        // `updating` event, so the version is bumped here.
        static::softDeleted(function (Model $model): void {
            $version = (int) $model->getAttribute('version') + 1;
            $model->newQueryWithoutScopes()->whereKey($model->getKey())->toBase()->update(['version' => $version]);
            $model->setAttribute('version', $version);
            $model->syncOriginalAttribute('version');
        });
    }

    /**
     * Rows changed after a pull position (`updated_at`, then id to break ties).
     *
     * @param  Builder<static>  $query
     */
    public function scopeChangedAfter(Builder $query, ?string $updatedAt, ?string $id): void
    {
        if ($updatedAt === null) {
            return;
        }

        $updatedColumn = $this->qualifyColumn($this->getUpdatedAtColumn());
        $keyColumn = $this->qualifyColumn($this->getKeyName());

        $query->where(function (Builder $q) use ($updatedColumn, $keyColumn, $updatedAt, $id): void {
            $q->where($updatedColumn, '>', $updatedAt)
                ->orWhere(fn (Builder $tie) => $tie->where($updatedColumn, '=', $updatedAt)->where($keyColumn, '>', (string) $id));
        });
    }
}
