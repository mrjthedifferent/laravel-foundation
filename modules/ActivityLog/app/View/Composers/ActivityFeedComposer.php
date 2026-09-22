<?php

namespace Modules\ActivityLog\View\Composers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mrj\Foundation\Support\WidgetComposer;
use Override;

/**
 * The dashboard's "Recent activity" card: the last few audited changes, who made
 * them and when.
 *
 * Read through the query builder with a join rather than an Eloquent relation:
 * the actor is one column, lazy loading is refused outside production, and the
 * payload has to be plain arrays to survive the dashboard cache.
 */
final class ActivityFeedComposer extends WidgetComposer
{
    #[Override]
    protected function permissions(): array
    {
        return ['View Activity Log'];
    }

    #[Override]
    protected function key(): string
    {
        return 'activity-feed';
    }

    #[Override]
    protected function build(): array
    {
        $table = (string) config('audit.drivers.database.table', 'audits');

        $rows = DB::table($table)
            ->leftJoin('users', 'users.id', '=', $table.'.user_id')
            ->select($table.'.event', $table.'.auditable_type', $table.'.created_at', 'users.name as actor')
            // Order by the primary key: it is indexed everywhere and rises with time.
            ->orderByDesc($table.'.id')
            ->limit(6)
            ->get();

        return [
            'entries' => $rows->map(fn (object $row): array => [
                'event' => (string) $row->event,
                'subject' => $this->subject((string) $row->auditable_type),
                'actor' => $row->actor === null ? null : (string) $row->actor,
                'at' => Carbon::parse($row->created_at)->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * `auditable_type` holds a morph alias ('setting'), not a class name, so the
     * label comes from the morph map where one is registered.
     */
    private function subject(string $type): string
    {
        $model = Relation::getMorphedModel($type);

        return Str::headline($model === null ? $type : class_basename($model));
    }
}
