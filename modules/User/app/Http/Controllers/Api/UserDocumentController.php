<?php

namespace Modules\User\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\UploadUserDocumentAction;
use Modules\User\Data\UserDocumentData;
use Modules\User\Transformers\UserDocumentResource;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

class UserDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = $request->user()->documents()->get();

        return JsonResponseFactory::success(__('user::user.flash.user_documents_list'), UserDocumentResource::collection($documents));
    }

    public function store(UserDocumentData $data, UploadUserDocumentAction $action): JsonResponse
    {
        // Validation is handled automatically by UserDocumentData injection

        $document = $action->execute(request()->user(), $data);

        return JsonResponseFactory::created(__('user::user.flash.document_uploaded'), UserDocumentResource::make($document));
    }
}
