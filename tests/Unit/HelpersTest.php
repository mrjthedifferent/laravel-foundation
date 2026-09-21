<?php

namespace Mrj\Foundation\Tests\Unit;

use Illuminate\Queue\WorkerStopReason;
use Mrj\Foundation\Support\Email;
use Mrj\Foundation\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_helpers_are_autoloaded(): void
    {
        foreach (['ajaxResponse', 'integerStatus', 'getCommonStatus', 'getParPagePaginate', 'currency_number', 'enum_value', 'allPermissions', 'mailAppName'] as $function) {
            $this->assertTrue(function_exists($function), "$function() is not loaded");
        }
    }

    public function test_enum_value_unwraps_backed_enums_and_passes_scalars_through(): void
    {
        $this->assertSame(WorkerStopReason::Interrupted->value, enum_value(WorkerStopReason::Interrupted));
        $this->assertSame('plain', enum_value('plain'));
        $this->assertNull(enum_value(null));
    }

    public function test_ajax_response_is_json(): void
    {
        $response = ajaxResponse(200, 'Saved', null, ['id' => 1]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Saved', $response->getData(true)['message']);
    }

    public function test_email_is_normalized(): void
    {
        $this->assertSame('someone@example.com', Email::normalize('  Someone@Example.COM '));
    }
}
