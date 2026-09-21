<?php

namespace Modules\ErrorReport\Actions;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ErrorReport\Models\ErrorReport;

final readonly class GetErrorReportsAction
{
    public function execute(Request $request): LengthAwarePaginator
    {
        return ErrorReport::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('message', 'like', '%'.$request->search.'%')
                        ->orWhere('exception_class', 'like', '%'.$request->search.'%')
                        ->orWhere('file', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('resolved'), function ($q) use ($request) {
                if ($request->resolved === '1') {
                    $q->whereNotNull('resolved_at');
                } else {
                    $q->whereNull('resolved_at');
                }
            })
            ->with('user')
            ->latest('last_seen_at')
            ->paginate((int) $request->input('per_page', 15));
    }
}
