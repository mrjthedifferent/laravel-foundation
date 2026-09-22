<?php

namespace Modules\ActivityLog\Http\Controllers;

use Illuminate\Http\Request;
use Modules\ActivityLog\Actions\DeleteActivityLogAction;
use Modules\ActivityLog\Actions\ExportActivityLogsAction;
use Modules\ActivityLog\Actions\GetActivityLogAction;
use Modules\ActivityLog\Actions\GetActivityLogsAction;
use Modules\ActivityLog\Actions\GetIpInfoAction;
use Modules\ActivityLog\Models\ActivityLog;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * ARCHITECTURE PATTERN: Ultra-Thin Web Controller
 * - Authorizes requests via $this->authorize()
 * - Executes business logic via Action classes
 * - Returns views / redirects only
 *
 * Business logic is in:
 * - Actions: GetActivityLogsAction, GetActivityLogAction, DeleteActivityLogAction,
 *            GetIpInfoAction, ExportActivityLogsAction
 * - Queries: ActivityLogQuery
 */
class ActivityLogController extends Controller
{
    public function index(Request $request, GetActivityLogsAction $action)
    {
        $this->authorize('viewAny', ActivityLog::class);

        ['audits' => $audits, 'eventTypes' => $eventTypes, 'auditableTypes' => $auditableTypes, 'users' => $users, 'impersonatedUsers' => $impersonatedUsers]
            = $action->execute($request);

        return view('activitylog::index', compact('audits', 'eventTypes', 'auditableTypes', 'users', 'impersonatedUsers'));
    }

    public function show(int $id, Request $request, GetActivityLogAction $action)
    {
        $this->authorize('viewAny', ActivityLog::class);

        ['audit' => $audit, 'audits' => $audits] = $action->execute($id, $request);

        return view('activitylog::show', compact('audits', 'audit'));
    }

    public function destroy(int $id, DeleteActivityLogAction $action)
    {
        $this->authorize('delete', ActivityLog::class);

        $action->execute($id);

        return redirect()->route('admin.activity-logs.index')
            ->with('success', __('activitylog::activitylog.flash.activity_log_deleted'));
    }

    public function trackIpInfo(Request $request, GetIpInfoAction $action)
    {
        $this->authorize('viewAny', ActivityLog::class);

        $ip = $request->get('ip');

        if (empty($ip)) {
            return '<div class="alert alert-danger">'.__('activitylog::activitylog.errors.no_ip_provided').'</div>';
        }

        $info = $action->execute($ip);

        if ($info['country'] === 'Unknown' && $info['location'] === null) {
            return '<div class="alert alert-warning">'.__('activitylog::activitylog.errors.ip_info_not_found', ['ip' => e($ip)]).'</div>';
        }

        return view('activitylog::partials.ip_info', $info);
    }

    public function export(Request $request, ExportActivityLogsAction $action)
    {
        $this->authorize('export', ActivityLog::class);

        $filters = array_merge($request->all(), ['format' => $request->input('format', 'xlsx')]);
        $action->execute($request->user(), $filters);

        return back()->with('success', __('activitylog::activitylog.flash.export_queued'));
    }
}
