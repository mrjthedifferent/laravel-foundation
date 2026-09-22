<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Built on the fly, rather than a static file, so it always matches the
 * columns BulkUserRowProcessor reads.
 */
final readonly class GenerateUserBulkUploadSampleAction
{
    public function execute(): StreamedResponse
    {
        return (new FastExcel(collect([[
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+8801712345678',
            'role' => 'User',
            'gender' => 'female',
            'password' => 'change-me-123',
            'is_active' => 1,
        ]])))->download('users_sample.xlsx');
    }
}
