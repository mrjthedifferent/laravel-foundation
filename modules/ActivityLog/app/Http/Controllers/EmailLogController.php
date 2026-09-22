<?php

namespace Modules\ActivityLog\Http\Controllers;

use Illuminate\Http\Request;
use Modules\ActivityLog\Actions\DeleteEmailLogAction;
use Modules\ActivityLog\Actions\GetEmailLogsAction;
use Modules\ActivityLog\Models\EmailLog;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * ARCHITECTURE PATTERN: Ultra-Thin Web Controller
 * - Authorizes requests via $this->authorize()
 * - Executes business logic via Action classes
 * - Returns views / redirects only
 *
 * Business logic is in:
 * - Actions: GetEmailLogsAction, DeleteEmailLogAction
 * - Queries: EmailLogQuery
 * - Listeners: LogEmailSending, LogEmailSent (capture every outgoing email)
 */
class EmailLogController extends Controller
{
    public function index(Request $request, GetEmailLogsAction $action)
    {
        $this->authorize('viewAny', EmailLog::class);

        $emailLogs = $action->execute($request);

        return view('activitylog::email-logs.index', compact('emailLogs'));
    }

    public function show(int $id)
    {
        $this->authorize('view', EmailLog::class);

        $emailLog = EmailLog::findOrFail($id);

        return view('activitylog::email-logs.show', compact('emailLog'));
    }

    public function destroy(int $id, DeleteEmailLogAction $action)
    {
        $this->authorize('delete', EmailLog::class);

        $action->execute($id);

        return redirect()->route('admin.email-logs.index')
            ->with('success', __('activitylog::activitylog.flash.email_log_deleted'));
    }
}
