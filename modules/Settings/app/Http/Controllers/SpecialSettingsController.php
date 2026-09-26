<?php

namespace Modules\Settings\Http\Controllers;

use Exception;
use Google_Client;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Modules\Notification\Jobs\SendSmsJob;
use Modules\Settings\Data\MailerData;
use Modules\Settings\Data\SmsGatewayData;
use Modules\Settings\Http\Requests\SendTestEmailRequest;
use Modules\Settings\Http\Requests\SendTestSmsRequest;
use Modules\Settings\Http\Requests\UpdateEmailMailersRequest;
use Modules\Settings\Http\Requests\UpdateFirebaseRequest;
use Modules\Settings\Http\Requests\UpdatePrivacyPolicyRequest;
use Modules\Settings\Http\Requests\UpdateSmsGatewaysRequest;
use Modules\Settings\Http\Requests\UpdateSocialAuthRequest;
use Modules\Settings\Http\Requests\UpdateTermsConditionsRequest;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\MailerSecretCipher;
use Modules\Settings\Services\MicrosoftOAuthTokenService;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

class SpecialSettingsController extends Controller
{
    /**
     * Display the privacy policy settings form
     */
    public function privacyPolicy(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);
        $setting = Setting::where('key', 'privacy_policy')->first();

        return view('settings::special.privacy-policy', compact('setting'));
    }

    /**
     * Update the privacy policy setting
     */
    public function updatePrivacyPolicy(UpdatePrivacyPolicyRequest $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        Setting::updateOrCreate(
            ['key' => 'privacy_policy'],
            [
                'value' => $request->validated('privacy_policy'),
                'type' => 'textarea',
                'group' => 'General',
                'description' => __('settings::settings.flash.privacy_policy_description'),
                'is_visible' => false,
            ]
        );

        return redirect()->back()->with('success', __('settings::settings.flash.privacy_policy_updated'));
    }

    /**
     * Display the terms and conditions settings form
     */
    public function termsConditions(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);
        $setting = Setting::where('key', 'terms_conditions')->first();

        return view('settings::special.terms-conditions', compact('setting'));
    }

    /**
     * Update the terms and conditions setting
     */
    public function updateTermsConditions(UpdateTermsConditionsRequest $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        Setting::updateOrCreate(
            ['key' => 'terms_conditions'],
            [
                'value' => $request->validated('terms_conditions'),
                'type' => 'textarea',
                'group' => 'General',
                'description' => __('settings::settings.flash.terms_conditions_description'),
                'is_visible' => false,
            ]
        );

        return redirect()->back()->with('success', __('settings::settings.flash.terms_conditions_updated'));
    }

    /**
     * Display the SMS gateways settings form
     */
    public function smsGateways(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);
        $smsGateways = Setting::where('key', 'sms_gateways')->first();
        $smsGateway = Setting::where('key', 'sms_gateway')->first();

        return view('settings::special.sms-gateways', compact('smsGateways', 'smsGateway'));
    }

    /**
     * Update the SMS gateways settings
     */
    public function updateSmsGateways(UpdateSmsGatewaysRequest $request, MailerSecretCipher $cipher): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $validatedGateways = $request->validated('sms_gateways');
        ksort($validatedGateways);

        // The stored VALUE of each gateway, so a blank header/param value
        // (masked in the form) keeps the current, already-encrypted one.
        $storedValues = $this->storedGatewayValues();

        $gatewaysArray = [];
        foreach ($validatedGateways as $gateway) {
            // SmsGatewayData folds the keys[]/values[] arrays into maps and
            // encrypts every header/param value.
            $gatewaysArray[] = SmsGatewayData::fromEntry($gateway)
                ->toEntry($cipher, $storedValues[$gateway['TYPE'] ?? ''] ?? []);
        }

        Setting::updateOrCreate(
            ['key' => 'sms_gateways'],
            ['type' => 'json', 'group' => 'General', 'value' => json_encode($gatewaysArray), 'is_visible' => false]
        );

        Setting::updateOrCreate(
            ['key' => 'sms_gateway'],
            ['type' => 'select', 'group' => 'General', 'value' => $request->validated('sms_gateway'), 'is_visible' => false]
        );

        return redirect()->back()->with('success', __('settings::settings.flash.sms_gateways_updated'));
    }

    /**
     * The stored VALUE of each SMS gateway, keyed by gateway name, so a blank
     * header/param value on update can retain its previous (encrypted) value.
     *
     * @return array<string, array<string, mixed>>
     */
    private function storedGatewayValues(): array
    {
        $stored = Setting::where('key', 'sms_gateways')->first();

        if (! $stored || ! is_array($stored->value)) {
            return [];
        }

        $values = [];
        foreach ($stored->value as $gateway) {
            if (isset($gateway['TYPE'])) {
                $values[$gateway['TYPE']] = is_array($gateway['VALUE'] ?? null) ? $gateway['VALUE'] : [];
            }
        }

        return $values;
    }

    /**
     * Display the email mailers settings form
     */
    public function emailMailers(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);
        $emailMailers = Setting::where('key', 'email_mailers')->first();
        $emailMailer = Setting::where('key', 'email_mailer')->first();

        return view('settings::special.email-mailers', compact('emailMailers', 'emailMailer'));
    }

    /**
     * Update the email mailers settings
     */
    public function updateEmailMailers(UpdateEmailMailersRequest $request, MicrosoftOAuthTokenService $tokens, MailerSecretCipher $cipher): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        // validated() fills its result in rule order, and Laravel moves wildcard
        // rules behind explicit per-index ones — so sort back into the order the
        // form submitted, otherwise the mailer list reshuffles on every save.
        $validatedMailers = $request->validated('email_mailers');
        ksort($validatedMailers);

        // The stored VALUE of each mailer, so a blank secret (never rendered
        // back into the form) keeps the current, already-encrypted one.
        $storedValues = $this->storedMailerValues();

        $mailersArray = [];
        foreach ($validatedMailers as $mailer) {
            // MailerData prunes the VALUE to the transport's own fields and
            // encrypts its secret, keeping the stored secret when left blank.
            $entry = MailerData::fromEntry($mailer)
                ->toEntry($cipher, $storedValues[$mailer['TYPE'] ?? ''] ?? []);

            $mailersArray[] = $entry;

            // A rotated secret must take effect on the next send, not once the
            // previously issued token happens to expire.
            $scope = match ($entry['VALUE']['transport'] ?? null) {
                'microsoft_oauth' => MicrosoftOAuthTokenService::SMTP_SCOPE,
                'microsoft_graph' => MicrosoftOAuthTokenService::GRAPH_SCOPE,
                default => null,
            };

            if ($scope !== null) {
                $tokens->forget(
                    (string) ($entry['VALUE']['tenant_id'] ?? ''),
                    (string) ($entry['VALUE']['client_id'] ?? ''),
                    $scope,
                );
            }
        }

        Setting::updateOrCreate(
            ['key' => 'email_mailers'],
            ['type' => 'json', 'group' => 'General', 'value' => json_encode($mailersArray), 'is_visible' => false]
        );

        Setting::updateOrCreate(
            ['key' => 'email_mailer'],
            ['type' => 'select', 'group' => 'General', 'value' => $request->validated('email_mailer'), 'is_visible' => false]
        );

        return redirect()->back()->with('success', __('settings::settings.flash.email_mailers_updated'));
    }

    /**
     * The stored VALUE of each mailer, keyed by mailer name, so a blank secret
     * submitted on update can retain its previous (encrypted) value.
     *
     * @return array<string, array<string, mixed>>
     */
    private function storedMailerValues(): array
    {
        $stored = Setting::where('key', 'email_mailers')->first();

        if (! $stored || ! is_array($stored->value)) {
            return [];
        }

        $values = [];
        foreach ($stored->value as $mailer) {
            if (isset($mailer['TYPE'])) {
                $values[$mailer['TYPE']] = is_array($mailer['VALUE'] ?? null) ? $mailer['VALUE'] : [];
            }
        }

        return $values;
    }

    /**
     * Send a test SMS
     */
    public function sendTestSMS(SendTestSmsRequest $request): JsonResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $phone = $request->validated('mobile_no');
        $message = __('settings::settings.flash.test_sms_body', ['app' => appName()]);
        SendSmsJob::dispatch($message, $phone);

        return JsonResponseFactory::success(
            __('settings::settings.flash.test_sms_success'),
            null
        );
    }

    /**
     * Send a test email
     */
    public function sendTestEmail(SendTestEmailRequest $request, MicrosoftOAuthTokenService $tokens): JsonResponse
    {
        $this->authorize('editSpecial', Setting::class);

        try {
            // Force a fresh token so the test actually exercises the stored
            // credentials rather than a token minted before they were corrected.
            $default = config('mail.default');
            if ($default === 'microsoft_oauth' || $default === 'microsoft_graph') {
                $tokens->forget(
                    (string) config("mail.mailers.{$default}.tenant_id"),
                    (string) config("mail.mailers.{$default}.client_id"),
                    $default === 'microsoft_graph'
                        ? MicrosoftOAuthTokenService::GRAPH_SCOPE
                        : MicrosoftOAuthTokenService::SMTP_SCOPE,
                );
            }

            $email = $request->validated('email');
            $subject = __('settings::settings.flash.test_email_subject', ['app' => appName()]);
            $message = __('settings::settings.flash.test_email_body', ['app' => appName()]);
            Mail::raw($message, static function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        } catch (Exception $e) {
            return JsonResponseFactory::error(
                __('settings::settings.errors.email_job_failed', ['message' => $e->getMessage()]),
                null,
                500
            );
        }

        return JsonResponseFactory::success(__('settings::settings.flash.test_email_success'), null);
    }

    /**
     * Display the Firebase settings form
     */
    public function firebase(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);
        $firebaseCredentialsJson = Setting::where('key', 'firebase_credentials_json')->first();
        $firebaseProjectId = Setting::where('key', 'firebase_project_id')->first();

        return view('settings::special.firebase', compact('firebaseCredentialsJson', 'firebaseProjectId'));
    }

    /**
     * Update the Firebase settings
     */
    public function updateFirebase(UpdateFirebaseRequest $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $keys = [
            'firebase_credentials_json' => ['description' => __('settings::settings.flash.firebase_credentials_description')],
            'firebase_project_id' => ['description' => __('settings::settings.flash.firebase_project_id_description')],
        ];

        foreach ($keys as $key => $meta) {
            // 'type' must be filled before 'value': Setting::setValueAttribute()
            // reads the sibling 'type' attribute to decide whether to encrypt.
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'type' => $key === 'firebase_credentials_json' ? 'encrypted' : 'text',
                    'value' => $request->validated($key, ''),
                    'group' => 'Firebase',
                    'description' => $meta['description'],
                    'is_visible' => false,
                ]
            );
        }

        return redirect()->back()->with('success', __('settings::settings.flash.firebase_updated'));
    }

    /**
     * Test Firebase connection using stored credentials
     */
    public function testFirebaseConnection(): JsonResponse
    {
        $this->authorize('editSpecial', Setting::class);
        $credentialsJson = getSystemSetting('firebase_credentials_json');

        if (empty($credentialsJson)) {
            return JsonResponseFactory::error(
                __('settings::settings.errors.firebase_not_configured'),
                null,
                400
            );
        }

        $credentials = is_string($credentialsJson) ? json_decode($credentialsJson, true) : $credentialsJson;
        if (! is_array($credentials) || empty($credentials['type']) || $credentials['type'] !== 'service_account') {
            return JsonResponseFactory::error(
                __('settings::settings.errors.firebase_invalid_credentials'),
                null,
                400
            );
        }

        try {
            $client = new Google_Client;
            $client->setAuthConfig($credentials);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->addScope('https://www.googleapis.com/auth/cloud-platform');

            $token = $client->fetchAccessTokenWithAssertion();
            $accessToken = $token['access_token'] ?? null;

            if (! $accessToken) {
                return JsonResponseFactory::error(
                    __('settings::settings.errors.firebase_no_token'),
                    null,
                    502
                );
            }

            return JsonResponseFactory::success(
                __('settings::settings.errors.firebase_connection_success'),
                null
            );
        } catch (Exception $e) {
            Log::warning('Firebase connection test failed: '.$e->getMessage());

            return JsonResponseFactory::error(
                __('settings::settings.errors.firebase_connection_failed', ['message' => $e->getMessage()]),
                null,
                502
            );
        }
    }

    /**
     * Social Auth setting keys and their config targets (for syncing before test).
     */
    private static function socialAuthKeys(): array
    {
        return [
            'google' => ['google_client_id', 'google_client_secret', 'google_redirect_uri'],
            'github' => ['github_client_id', 'github_client_secret', 'github_redirect_uri'],
            'apple' => ['apple_client_id', 'apple_client_secret', 'apple_redirect_uri', 'apple_team_id', 'apple_key_id', 'apple_key_file'],
        ];
    }

    /**
     * Display the Social Auth settings form
     */
    public function socialAuth(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);
        $keys = array_merge(
            self::socialAuthKeys()['google'],
            self::socialAuthKeys()['github'],
            self::socialAuthKeys()['apple']
        );
        $settings = Setting::whereIn('key', $keys)->get()->keyBy('key');

        return view('settings::special.social-auth', compact('settings'));
    }

    /**
     * Update the Social Auth settings
     */
    public function updateSocialAuth(UpdateSocialAuthRequest $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $keys = array_merge(
            self::socialAuthKeys()['google'],
            self::socialAuthKeys()['github'],
            self::socialAuthKeys()['apple']
        );
        $configMap = [
            'google_client_id' => 'services.google.client_id',
            'google_client_secret' => 'services.google.client_secret',
            'google_redirect_uri' => 'services.google.redirect',
            'github_client_id' => 'services.github.client_id',
            'github_client_secret' => 'services.github.client_secret',
            'github_redirect_uri' => 'services.github.redirect',
            'apple_client_id' => 'services.apple.client_id',
            'apple_client_secret' => 'services.apple.client_secret',
            'apple_redirect_uri' => 'services.apple.redirect',
            'apple_team_id' => 'services.apple.team_id',
            'apple_key_id' => 'services.apple.key_id',
            'apple_key_file' => 'services.apple.key_file',
        ];

        // OAuth client secrets are stored encrypted. apple_key_file is a filesystem
        // path to the .p8 key, not the key material itself, so it stays plain text.
        $secretKeys = ['google_client_secret', 'github_client_secret', 'apple_client_secret'];

        foreach ($keys as $key) {
            $value = $request->validated($key, '');
            if (isset($configMap[$key])) {
                config([$configMap[$key] => $value]);
            }
            // 'type' must be filled before 'value': Setting::setValueAttribute()
            // reads the sibling 'type' attribute to decide whether to encrypt.
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'type' => in_array($key, $secretKeys, true) ? 'encrypted' : 'text',
                    'value' => $value,
                    'group' => 'Social Auth',
                    'is_visible' => false,
                ]
            );
        }

        app(SettingsRepository::class)->forget();

        return redirect()->back()->with('success', __('settings::settings.flash.social_auth_updated'));
    }

    /**
     * Test Google OAuth configuration by building the redirect URL.
     */
    public function testGoogleAuth(Request $request): JsonResponse
    {
        $this->authorize('editSpecial', Setting::class);

        return $this->testSocialProvider($request, 'google', 'Google', ['google_client_id', 'google_client_secret', 'google_redirect_uri']);
    }

    /**
     * Test GitHub OAuth configuration by building the redirect URL.
     */
    public function testGithubAuth(Request $request): JsonResponse
    {
        $this->authorize('editSpecial', Setting::class);

        return $this->testSocialProvider($request, 'github', 'GitHub', ['github_client_id', 'github_client_secret', 'github_redirect_uri']);
    }

    /**
     * Test Apple OAuth configuration by building the redirect URL.
     */
    public function testAppleAuth(Request $request): JsonResponse
    {
        $this->authorize('editSpecial', Setting::class);

        return $this->testSocialProvider($request, 'apple', 'Apple', ['apple_client_id', 'apple_client_secret', 'apple_redirect_uri', 'apple_team_id', 'apple_key_id', 'apple_key_file']);
    }

    /**
     * Test a social provider using request values (current form) or saved settings, then building the auth URL.
     */
    private function testSocialProvider(Request $request, string $provider, string $label, array $requiredKeys): JsonResponse
    {
        $values = [];
        foreach ($requiredKeys as $key) {
            $value = $request->input($key) ?? getSystemSetting($key);
            $value = is_string($value) ? trim($value) : $value;
            if ($value === null || $value === '') {
                return JsonResponseFactory::error(
                    __('settings::settings.errors.social_provider_not_configured', ['label' => $label]),
                    null,
                    400
                );
            }
            $values[$key] = $value;
        }

        $map = [
            'google_client_id' => 'services.google.client_id',
            'google_client_secret' => 'services.google.client_secret',
            'google_redirect_uri' => 'services.google.redirect',
            'github_client_id' => 'services.github.client_id',
            'github_client_secret' => 'services.github.client_secret',
            'github_redirect_uri' => 'services.github.redirect',
            'apple_client_id' => 'services.apple.client_id',
            'apple_client_secret' => 'services.apple.client_secret',
            'apple_redirect_uri' => 'services.apple.redirect',
            'apple_team_id' => 'services.apple.team_id',
            'apple_key_id' => 'services.apple.key_id',
            'apple_key_file' => 'services.apple.key_file',
        ];

        foreach ($values as $key => $value) {
            if (isset($map[$key])) {
                config([$map[$key] => $value]);
            }
        }

        try {
            $url = Socialite::driver($provider)->redirect()->getTargetUrl();

            if (empty($url) || ! str_starts_with($url, 'http')) {
                return JsonResponseFactory::error(
                    __('settings::settings.errors.social_provider_invalid_redirect', ['label' => $label]),
                    null,
                    502
                );
            }

            return JsonResponseFactory::success(
                __('settings::settings.errors.social_provider_test_success', ['label' => $label]),
                null
            );
        } catch (Exception $e) {
            Log::warning("Social Auth test failed for {$provider}: ".$e->getMessage());

            return JsonResponseFactory::error(
                __('settings::settings.errors.social_provider_test_failed', ['label' => $label, 'message' => $e->getMessage()]),
                null,
                502
            );
        }
    }
}
