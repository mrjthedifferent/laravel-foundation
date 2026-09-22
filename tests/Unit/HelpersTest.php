<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Unit;

use Mrj\Foundation\Support\Email;
use Mrj\Foundation\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_helpers_are_autoloaded(): void
    {
        foreach (['integerStatus', 'getParPagePaginate', 'form_old_key', 'display_label', 'mailAppName'] as $function) {
            $this->assertTrue(function_exists($function), "$function() is not loaded");
        }
    }

    public function test_form_old_key_converts_bracketed_names_to_dot_notation(): void
    {
        $this->assertSame('roles', form_old_key('roles[]'));
        $this->assertSame('sms_gateways.0.VALUE.endpoint', form_old_key('sms_gateways[0][VALUE][endpoint]'));
        $this->assertSame('option_keys', form_old_key('option_keys[]'));
    }

    public function test_display_label_translates_through_json_and_never_returns_an_array(): void
    {
        app('translator')->addLines(['*.View Role' => 'Rolle anzeigen'], 'de', '*');
        app()->setLocale('de');

        $this->assertSame('Rolle anzeigen', display_label('View Role'));
        $this->assertSame('Untranslated Label', display_label('Untranslated Label'));
        // A stored name equal to a lang file name makes __() return the whole file.
        $this->assertIsArray(__('validation'));
        $this->assertSame('validation', display_label('validation'));
        $this->assertSame('', display_label(null));
    }

    public function test_email_is_normalized(): void
    {
        $this->assertSame('someone@example.com', Email::normalize('  Someone@Example.COM '));
    }

    public function test_escape_like_neutralizes_wildcard_characters(): void
    {
        $this->assertSame('100\%', escapeLike('100%'));
        $this->assertSame('a\_b', escapeLike('a_b'));
        $this->assertSame('a\\\\b', escapeLike('a\\b'));
        $this->assertSame('plain', escapeLike('plain'));
    }

    public function test_capped_per_page_clamps_to_the_maximum_and_never_goes_below_one(): void
    {
        $this->assertSame(15, cappedPerPage(15));
        $this->assertSame(100, cappedPerPage(999999999));
        $this->assertSame(1, cappedPerPage(0));
        $this->assertSame(1, cappedPerPage(-50));
        $this->assertSame(200, cappedPerPage(999999, max: 200));
    }
}
