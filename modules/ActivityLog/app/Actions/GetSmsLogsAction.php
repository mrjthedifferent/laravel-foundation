<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ActivityLog\Queries\SmsLogQuery;

final readonly class GetSmsLogsAction
{
    public function execute(Request $request): LengthAwarePaginator
    {
        return SmsLogQuery::make()
            ->search($request->input('search'))
            ->filterByStatus($request->input('status'))
            ->filterByDateFrom($request->input('date_from'))
            ->filterByDateTo($request->input('date_to'))
            ->orderByLatest()
            ->paginate((int) $request->input('per_page', 15));
    }
}
