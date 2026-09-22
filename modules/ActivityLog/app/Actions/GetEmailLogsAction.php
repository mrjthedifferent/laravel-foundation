<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ActivityLog\Queries\EmailLogQuery;

final readonly class GetEmailLogsAction
{
    public function execute(Request $request): LengthAwarePaginator
    {
        return EmailLogQuery::make()
            ->search($request->input('search'))
            ->filterByStatus($request->input('status'))
            ->filterByDateFrom($request->input('date_from'))
            ->filterByDateTo($request->input('date_to'))
            ->orderByLatest()
            ->paginate(cappedPerPage((int) $request->input('per_page', 15)));
    }
}
