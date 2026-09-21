<?php

namespace Modules\ActivityLog\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\ActivityLog\Helpers\ActivityLogHelper;
use Modules\ActivityLog\Queries\ActivityLogQuery;
use OwenIt\Auditing\Models\Audit;

final readonly class GetActivityLogsAction
{
    /**
     * Apply all request filters and return a paginated result + filter metadata.
     *
     * @return array{audits: LengthAwarePaginator, eventTypes: array, auditableTypes: array, users: \Illuminate\Database\Eloquent\Collection, impersonatedUsers: Collection<int, string>}
     */
    public function execute(Request $request): array
    {
        $audits = ActivityLogQuery::make()
            ->search($request->input('search'))
            ->filterByEvent($request->input('event'))
            ->filterByAuditableType($request->input('auditable_type'))
            ->filterByUserId($request->input('user_id'))
            ->filterByDateFrom($request->input('date_from'))
            ->filterByDateTo($request->input('date_to'))
            ->withUser()
            ->orderByLatest()
            ->paginate(cappedPerPage((int) $request->input('per_page', 15)));

        $eventTypes = ActivityLogHelper::getEventTypes();

        $auditableTypes = Audit::select('auditable_type')
            ->distinct()
            ->whereNotNull('auditable_type')
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->toArray();

        // Users who have audit records — for the filter dropdown
        $users = User::whereIn('id',
            Audit::whereNotNull('user_id')->distinct()->pluck('user_id')
        )->orderBy('name')->get(['id', 'name']);

        // Names of the accounts a Super Admin was impersonating on this page's rows
        $impersonatedUsers = User::whereIn('id',
            $audits->getCollection()
                ->map(fn (Audit $audit): ?int => ActivityLogHelper::impersonatedUserId($audit->tags))
                ->filter()
                ->unique()
        )->pluck('name', 'id');

        return compact('audits', 'eventTypes', 'auditableTypes', 'users', 'impersonatedUsers');
    }
}
