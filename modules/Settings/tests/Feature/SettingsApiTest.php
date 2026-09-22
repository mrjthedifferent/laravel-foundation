<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_fetch_privacy_policy_via_api(): void
    {
        // Create privacy policy setting
        Setting::create([
            'key' => 'privacy_policy',
            'value' => '<h1>Privacy Policy</h1><p>This is our privacy policy.</p>',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        $response = $this->getJson('/api/v1/settings/privacy-policy');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'privacy_policy',
                'updated_at',
            ],
            'timestamp',
        ]);
        $response->assertJson([
            'success' => true,
            'message' => 'Privacy Policy retrieved successfully',
        ]);
        $response->assertJsonPath('data.privacy_policy', '<h1>Privacy Policy</h1><p>This is our privacy policy.</p>');
    }

    public function test_returns_404_when_privacy_policy_not_found(): void
    {
        $response = $this->getJson('/api/v1/settings/privacy-policy');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Privacy Policy not found',
        ]);
    }

    public function test_can_fetch_terms_conditions_via_api(): void
    {
        // Create terms & conditions setting
        Setting::create([
            'key' => 'terms_conditions',
            'value' => '<h1>Terms & Conditions</h1><p>These are our terms.</p>',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        $response = $this->getJson('/api/v1/settings/terms-conditions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'terms_conditions',
                'updated_at',
            ],
            'timestamp',
        ]);
        $response->assertJson([
            'success' => true,
            'message' => 'Terms & Conditions retrieved successfully',
        ]);
        $response->assertJsonPath('data.terms_conditions', '<h1>Terms & Conditions</h1><p>These are our terms.</p>');
    }

    public function test_returns_404_when_terms_conditions_not_found(): void
    {
        $response = $this->getJson('/api/v1/settings/terms-conditions');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Terms & Conditions not found',
        ]);
    }

    public function test_api_returns_updated_timestamp(): void
    {
        $setting = Setting::create([
            'key' => 'privacy_policy',
            'value' => 'Test content',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        $response = $this->getJson('/api/v1/settings/privacy-policy');

        $response->assertStatus(200);
        $response->assertJsonPath('data.updated_at', $setting->updated_at->toJSON());
    }

    public function test_both_apis_work_independently(): void
    {
        // Create both settings
        Setting::create([
            'key' => 'privacy_policy',
            'value' => 'Privacy content',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        Setting::create([
            'key' => 'terms_conditions',
            'value' => 'Terms content',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        // Test privacy policy endpoint
        $privacyResponse = $this->getJson('/api/v1/settings/privacy-policy');
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertJsonPath('data.privacy_policy', 'Privacy content');

        // Test terms & conditions endpoint
        $termsResponse = $this->getJson('/api/v1/settings/terms-conditions');
        $termsResponse->assertStatus(200);
        $termsResponse->assertJsonPath('data.terms_conditions', 'Terms content');
    }

    public function test_api_handles_html_content_correctly(): void
    {
        $htmlContent = '<div class="content"><h1>Title</h1><p>Paragraph with <strong>bold</strong> text.</p></div>';

        Setting::create([
            'key' => 'privacy_policy',
            'value' => $htmlContent,
            'type' => 'textarea',
            'group' => 'General',
        ]);

        $response = $this->getJson('/api/v1/settings/privacy-policy');

        $response->assertStatus(200);
        $response->assertJsonPath('data.privacy_policy', $htmlContent);
    }

    public function test_api_handles_empty_content(): void
    {
        Setting::create([
            'key' => 'terms_conditions',
            'value' => '',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        $response = $this->getJson('/api/v1/settings/terms-conditions');

        $response->assertStatus(200);
        $response->assertJsonPath('data.terms_conditions', '');
    }
}
