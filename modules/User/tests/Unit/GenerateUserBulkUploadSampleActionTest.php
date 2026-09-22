<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit;

use Modules\User\Actions\GenerateUserBulkUploadSampleAction;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class GenerateUserBulkUploadSampleActionTest extends TestCase
{
    public function test_it_builds_a_downloadable_xlsx_sample(): void
    {
        $response = (new GenerateUserBulkUploadSampleAction)->execute();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('users_sample.xlsx', $response->headers->get('content-disposition'));
    }
}
