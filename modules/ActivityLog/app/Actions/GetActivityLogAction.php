<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use OwenIt\Auditing\Models\Audit;

final readonly class GetActivityLogAction
{
    /**
     * Fetch a single audit record together with paginated sibling audits.
     *
     * @return array{audit: Audit, audits: LengthAwarePaginator}
     */
    public function execute(int $id, Request $request): array
    {
        $audit = Audit::findOrFail($id);

        $audits = Audit::where('auditable_id', $audit->auditable_id)
            ->where('auditable_type', $audit->auditable_type)
            ->with('user.roles')
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return compact('audit', 'audits');
    }
}
