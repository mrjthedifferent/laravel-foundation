<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mrj\Foundation\Sync\SyncOperation;
use Mrj\Foundation\Sync\SyncResult;

/**
 * Connects one offline-sync collection (e.g. "farms") to a Syncable model:
 * which rows a user may receive, how a row is serialised, and how a pushed
 * change is validated, authorised and saved. Register handlers in
 * `foundation.offline_sync.handlers`. Extend Mrj\Foundation\Sync\ModelSyncHandler
 * rather than implementing this from scratch.
 *
 * @api
 */
interface SyncHandler
{
    /**
     * Every row the user may receive, soft-deleted ones included (they
     * become tombstones).
     *
     * @return Builder<Model>
     */
    public function visibleTo(Authenticatable $user): Builder;

    /**
     * The row as sent to clients.
     *
     * @return array<string, mixed>
     */
    public function toSync(Model $model): array;

    /**
     * Validate, authorise and save one pushed change.
     */
    public function apply(Authenticatable $user, SyncOperation $op): SyncResult;
}
