<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Modules\Settings\Support\LegalHtml;
use Tests\TestCase;

/**
 * The public privacy policy and terms pages: no login, the admin's text, cleaned.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function store(string $key, string $html): void
    {
        Setting::create(['key' => $key, 'group' => 'General', 'type' => 'textarea', 'value' => $html, 'is_visible' => false]);
    }

    public function test_both_pages_are_public_and_show_the_admins_text(): void
    {
        $this->store('privacy_policy', '<h1>Privacy</h1><p>We keep your farm data private.</p>');
        $this->store('terms_conditions', '<h1>Terms</h1><ol><li>Be fair.</li></ol>');

        $this->get('/privacy-policy')->assertOk()
            ->assertSee('<title>Privacy Policy', false)
            ->assertSee('<p>We keep your farm data private.</p>', false)
            ->assertSee('Last updated');
        $this->get('/terms-conditions')->assertOk()->assertSee('<li>Be fair.</li>', false);

        $this->assertSame(url('/privacy-policy'), route('legal.privacy_policy'));
    }

    public function test_a_missing_or_empty_text_is_a_404(): void
    {
        $this->get('/privacy-policy')->assertNotFound();

        $this->store('terms_conditions', '<p><script>x()</script></p>');
        $this->get('/terms-conditions')->assertNotFound();
    }

    public function test_scripts_handlers_and_unsafe_links_are_removed(): void
    {
        $clean = LegalHtml::clean(
            '<h2 onclick="steal()" style="color:red">Data</h2>'
            .'<p>Read <a href="javascript:alert(1)">this</a>, <a href=" java&#9;script:alert(1)">that</a> and <a href="https://example.com/x" onmouseover="x()">ours</a>.</p>'
            .'<script>alert(1)</script><iframe src="https://evil.test"></iframe><img src=x onerror=alert(1)>'
            .'<p class="ql-align-center evil">Centred</p><!-- note --><form><input name="a"></form>'
            .'<custom-tag>kept text</custom-tag>'
        );

        $this->assertStringContainsString('<h2>Data</h2>', $clean);
        $this->assertStringNotContainsString('javascript', $clean);
        $this->assertStringContainsString('<a>this</a>', $clean);
        $this->assertStringContainsString('<a href="https://example.com/x" rel="noopener noreferrer nofollow" target="_blank">ours</a>', $clean);
        foreach (['<script', 'alert(1)', '<iframe', '<img', 'onerror', 'onclick', 'onmouseover', 'style=', '<form', '<input', '<!--', 'evil'] as $gone) {
            $this->assertStringNotContainsString($gone, $clean, $gone);
        }
        $this->assertStringContainsString('kept text', $clean);
        $this->assertStringContainsString('Centred', $clean);
    }

    public function test_bengali_and_other_unicode_text_survives(): void
    {
        $this->assertSame('<p>আপনার তথ্য সুরক্ষিত।</p>', LegalHtml::clean('<p>আপনার তথ্য সুরক্ষিত।</p>'));
    }

    public function test_the_app_settings_api_gives_the_page_urls(): void
    {
        $this->getJson('/api/'.config('foundation.routing.api_prefix').'/settings/app')
            ->assertOk()
            ->assertJsonPath('data.privacy_policy_url', url('/privacy-policy'))
            ->assertJsonPath('data.terms_conditions_url', url('/terms-conditions'));
    }
}
