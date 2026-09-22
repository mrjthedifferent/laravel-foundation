<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Http\Controllers;

use Illuminate\Http\Request;
use Modules\ActivityLog\Actions\DeleteSmsLogAction;
use Modules\ActivityLog\Actions\GetSmsLogsAction;
use Modules\ActivityLog\Models\SmsLog;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * ARCHITECTURE PATTERN: Ultra-Thin Web Controller
 * - Authorizes requests via $this->authorize()
 * - Executes business logic via Action classes
 * - Returns views / redirects only
 *
 * Business logic is in:
 * - Actions: GetSmsLogsAction, DeleteSmsLogAction
 * - Queries: SmsLogQuery
 */
class SmsLogController extends Controller
{
    public function index(Request $request, GetSmsLogsAction $action)
    {
        $this->authorize('viewAny', SmsLog::class);

        $smsLogs = $action->execute($request);

        return view('activitylog::sms-logs.index', compact('smsLogs'));
    }

    public function destroy(int $id, DeleteSmsLogAction $action)
    {
        $this->authorize('delete', SmsLog::class);

        $action->execute($id);

        return redirect()->route('admin.sms-logs.index')
            ->with('success', __('activitylog::activitylog.flash.sms_log_deleted'));
    }
}
