<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Everything changed since the client's cursor, per collection: live rows in
 * `changes`, deleted ones in `tombstones`. At most `$limit` rows come back per
 * collection; `has_more` tells the client to pull again with the new cursor.
 *
 * Only rows last changed at least `settle_seconds` ago are returned, so a row
 * from a transaction that commits late cannot fall behind an advancing cursor.
 *
 * @api
 */
final readonly class PullSyncChanges
{
    /**
     * @param  list<string>|null  $only  Collections to pull; null for all.
     * @return array{changes: array<string, list<array<string, mixed>>>, tombstones: array<string, list<string>>, cursor: string, has_more: bool, server_time: string}
     */
    public function execute(Authenticatable $user, ?string $cursor, ?array $only, int $limit): array
    {
        $position = SyncCursor::decode($cursor);
        $settle = (int) config('foundation.offline_sync.settle_seconds', 2);
        $changes = [];
        $tombstones = [];
        $hasMore = false;

        foreach (SyncRegistry::names() as $name) {
            if ($only !== null && ! in_array($name, $only, true)) {
                continue;
            }

            $handler = SyncRegistry::handler($name);
            $query = $handler->visibleTo($user)->withoutGlobalScope(SoftDeletingScope::class);
            $model = $query->getModel();
            $updatedColumn = $model->qualifyColumn($model->getUpdatedAtColumn());
            $keyColumn = $model->qualifyColumn($model->getKeyName());
            $cutoff = now()->subSeconds($settle)->format($model->getDateFormat());

            [$after, $afterId] = $position->positionOf($name) ?? [null, null];

            if ($after !== null) {
                $query->where(function ($q) use ($updatedColumn, $keyColumn, $after, $afterId): void {
                    $q->where($updatedColumn, '>', $after)
                        ->orWhere(fn ($tie) => $tie->where($updatedColumn, '=', $after)->where($keyColumn, '>', $afterId));
                });
            }

            $rows = $query
                ->where($updatedColumn, '<=', $cutoff)
                ->orderBy($updatedColumn)
                ->orderBy($keyColumn)
                ->limit($limit + 1)
                ->get();

            if ($rows->count() > $limit) {
                $hasMore = true;
                $rows = $rows->take($limit);
            }

            foreach ($rows as $row) {
                if (method_exists($row, 'trashed') && $row->trashed()) {
                    $tombstones[$name][] = (string) $row->getKey();
                } else {
                    $changes[$name][] = $handler->toSync($row);
                }
            }

            $last = $rows->last();

            if ($last instanceof Model) {
                $position->advance(
                    $name,
                    $last->getAttribute($last->getUpdatedAtColumn())->format($last->getDateFormat()),
                    (string) $last->getKey(),
                );
            }
        }

        return [
            'changes' => $changes,
            'tombstones' => $tombstones,
            'cursor' => $position->encode(),
            'has_more' => $hasMore,
            'server_time' => now()->toIso8601String(),
        ];
    }
}
