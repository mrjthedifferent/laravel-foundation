<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Applies a batch of pushed changes in order. Each operation runs in its own
 * transaction, so one rejected or failing change does not undo the others;
 * the client gets one result per operation.
 *
 * @api
 */
final readonly class PushSyncChanges
{
    /**
     * @param  list<SyncOperation>  $ops
     * @return list<SyncResult>
     */
    public function execute(Authenticatable $user, array $ops): array
    {
        $results = [];

        foreach ($ops as $op) {
            if (! SyncRegistry::has($op->name)) {
                $results[] = SyncResult::rejected($op, ['name' => [__('foundation::foundation.offline_sync.unknown_collection')]]);

                continue;
            }

            try {
                $results[] = DB::transaction(fn (): SyncResult => SyncRegistry::handler($op->name)->apply($user, $op));
            } catch (Throwable $e) {
                report($e);
                $results[] = SyncResult::error($op, ['id' => [__('foundation::foundation.offline_sync.failed')]]);
            }
        }

        return $results;
    }
}
