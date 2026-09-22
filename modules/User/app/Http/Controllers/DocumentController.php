<?php

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Modules\User\Actions\UploadUserDocumentAction;
use Modules\User\Data\UserDocumentData;
use Modules\User\Http\Requests\UploadDocumentRequest;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * Web Controller for User Document Management
 *
 * ARCHITECTURE PATTERN: Ultra-Thin Controller
 * - Validates requests via Form Requests
 * - Executes business logic via Action classes
 * - Returns redirects only
 */
class DocumentController extends Controller
{
    /**
     * Upload document - Ultra-Thin Pattern
     */
    public function store(UploadDocumentRequest $request, User $user, UploadUserDocumentAction $action): RedirectResponse
    {
        $this->authorize('uploadDocument', $user);

        $action->execute($user, UserDocumentData::from($request->validated()));

        return redirect(route('admin.users.show', $user->id).'#documents')
            ->with('success', 'Document uploaded successfully');
    }

    /**
     * Delete document
     *
     * File cleanup is handled automatically by UserDocument model events.
     */
    public function destroy(User $user, int $documentId): RedirectResponse
    {
        $this->authorize('uploadDocument', $user); // Same permission as upload

        $document = $user->documents()->findOrFail($documentId);
        $document->delete();

        return redirect(route('admin.users.show', $user->id).'#documents')
            ->with('success', 'Document deleted successfully');
    }
}
