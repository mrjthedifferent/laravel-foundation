<?php

namespace Modules\ErrorReport\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ErrorReport\Actions\DeleteErrorReportAction;
use Modules\ErrorReport\Actions\GetErrorReportsAction;
use Modules\ErrorReport\Actions\ResolveErrorReportAction;
use Modules\ErrorReport\Models\ErrorReport;
use Mrj\Foundation\Http\Controllers\Controller;

class ErrorReportController extends Controller
{
    public function index(Request $request, GetErrorReportsAction $action): View
    {
        $this->authorize('viewAny', ErrorReport::class);

        $errorReports = $action->execute($request);

        return view('errorreport::index', compact('errorReports'));
    }

    public function show(ErrorReport $errorReport): View
    {
        $this->authorize('view', $errorReport);

        $errorReport->load('user');

        return view('errorreport::show', compact('errorReport'));
    }

    public function resolve(ErrorReport $errorReport, ResolveErrorReportAction $action): RedirectResponse
    {
        $this->authorize('resolve', $errorReport);

        $action->execute($errorReport);

        return redirect()->route('admin.error-reports.index')
            ->with('success', 'Error report marked as resolved.');
    }

    public function destroy(ErrorReport $errorReport, DeleteErrorReportAction $action): RedirectResponse
    {
        $this->authorize('delete', $errorReport);

        $action->execute($errorReport);

        return redirect()->route('admin.error-reports.index')
            ->with('success', 'Error report deleted.');
    }
}
