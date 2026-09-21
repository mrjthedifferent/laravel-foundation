<?php

namespace Modules\User\Actions;

use App\Models\User;
use Modules\User\Data\UserDocumentData;
use Modules\User\Models\UserDocument;

/**
 * Upload User Document Action
 *
 * ULTRA-THIN: Model mutators handle file uploads automatically
 */
final readonly class UploadUserDocumentAction
{
    public function execute(User $user, UserDocumentData $data): UserDocument
    {
        // Check if a document of the same type already exists for this user
        $existingDocument = UserDocument::query()
            ->where('user_id', $user->id)
            ->where('document_type', $data->document_type)
            ->first();

        $documentData = [
            'document_number' => $data->document_number,
            'file_path' => $data->file,  // Mutator handles upload automatically
            'back_file_path' => $data->back_file,  // Mutator handles upload automatically
            'expiry_date' => $data->expiry_date,
        ];

        if ($existingDocument) {
            // Update existing document - mutators handle file uploads & old file deletion
            $existingDocument->update($documentData);

            return $existingDocument;
        }

        // Create new document - mutators handle file uploads
        return UserDocument::create([
            'user_id' => $user->id,
            'document_type' => $data->document_type,
            ...$documentData,
        ]);
    }
}
