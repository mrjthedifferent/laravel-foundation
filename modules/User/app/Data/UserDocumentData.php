<?php

namespace Modules\User\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * User Document Data Transfer Object
 *
 * Pure DTO — carries validated field values between layers.
 * Validation rules live in UploadDocumentRequest.
 */
class UserDocumentData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $document_type,

        #[Required, File, Mimes(['pdf', 'jpg', 'jpeg', 'png']), Max(5120)]
        public UploadedFile $file,

        #[Nullable, StringType]
        public ?string $document_number = null,

        #[Nullable, File, Mimes(['pdf', 'jpg', 'jpeg', 'png']), Max(5120)]
        public ?UploadedFile $back_file = null,

        #[Nullable, Date]
        public ?string $expiry_date = null,
    ) {}
}
