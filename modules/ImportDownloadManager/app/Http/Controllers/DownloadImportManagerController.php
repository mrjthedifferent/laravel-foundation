<?php

namespace Modules\ImportDownloadManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\ImportDownloadManager\Actions\DeleteImportFileAction;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\ImportDownloadManager\Queries\DownloadImportQuery;
use Modules\ImportDownloadManager\Support\ImportFileDisk;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadImportManagerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DownloadImportManager::class);
        $status = $request->filled('status') ? ImportStatus::tryFrom($request->input('status')) : null;
        $type = $request->filled('type') ? ImportType::tryFrom($request->input('type')) : null;

        $records = DownloadImportQuery::make()
            ->forUser(Auth::id())
            ->filterByStatus($status)
            ->filterByType($type)
            ->search($request->input('search'))
            ->orderByLatest()
            ->paginate(perPage());

        return view('importdownloadmanager::index', [
            'downloadImports' => $records,
            'statuses' => ImportStatus::options(),
            'types' => ImportType::options(),
        ]);
    }

    public function destroy(DownloadImportManager $downloadImportManager, DeleteImportFileAction $action): RedirectResponse
    {
        $this->authorize('delete', $downloadImportManager);

        if (! $downloadImportManager->status->isDeletable()) {
            return redirect()->back()->with('error', __('importdownloadmanager::importdownloadmanager.flash.cannot_delete'));
        }

        $action->execute($downloadImportManager);
        $downloadImportManager->delete();

        return redirect()->back()->with('success', __('importdownloadmanager::importdownloadmanager.flash.deleted'));
    }

    /**
     * Return latest status for a set of record IDs (polled by frontend).
     */
    public function statusUpdate(Request $request): JsonResponse
    {
        $this->authorize('statusUpdate', DownloadImportManager::class);

        $ids = array_filter((array) $request->input('ids', []));

        if (empty($ids)) {
            return JsonResponseFactory::success(__('importdownloadmanager::importdownloadmanager.errors.no_records_to_update'), []);
        }

        $records = DownloadImportQuery::make()
            ->forUser(Auth::id())
            ->findByIds($ids)
            ->map(fn ($r) => [
                'id' => $r->id,
                'status' => $r->status->value,
                'url' => $r->url,
                'remarks' => $r->remarks,
            ]);

        return JsonResponseFactory::success('Statuses retrieved.', $records->values()->all());
    }

    public function download(DownloadImportManager $downloadImportManager): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $downloadImportManager);

        // Exports are written to the private disk; uploads (and files from before that
        // change) still live on the public one, so both are checked.
        $disk = ImportFileDisk::forRecord($downloadImportManager);

        if ($disk === null) {
            return redirect()->back()->with('error', __('importdownloadmanager::importdownloadmanager.flash.file_not_found'));
        }

        return Storage::disk($disk)->download($downloadImportManager->url);
    }
}
