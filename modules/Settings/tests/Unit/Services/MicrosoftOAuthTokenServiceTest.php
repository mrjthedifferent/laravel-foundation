<?php

namespace Modules\Settings\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Settings\Services\MicrosoftOAuthTokenService;
use RuntimeException;
use Tests\TestCase;

class MicrosoftOAuthTokenServiceTest extends TestCase
{
    private const TOKEN_URL = 'https://login.microsoftonline.com/*';

    private MicrosoftOAuthTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = app(MicrosoftOAuthTokenService::class);
    }

    public function test_it_fetches_an_access_token_with_the_client_credentials_grant(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'token-abc', 'expires_in' => 3599]),
        ]);

        $token = $this->service->accessToken('tenant-1', 'client-1', 'secret-1');

        $this->assertSame('token-abc', $token);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://login.microsoftonline.com/tenant-1/oauth2/v2.0/token'
                && $request['grant_type'] === 'client_credentials'
                && $request['client_id'] === 'client-1'
                && $request['client_secret'] === 'secret-1'
                && $request['scope'] === 'https://outlook.office365.com/.default';
        });
    }

    public function test_it_requests_the_given_scope(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'graph-token', 'expires_in' => 3599]),
        ]);

        $token = $this->service->accessToken('tenant-1', 'client-1', 'secret-1', MicrosoftOAuthTokenService::GRAPH_SCOPE);

        $this->assertSame('graph-token', $token);

        Http::assertSent(function ($request) {
            return $request['scope'] === 'https://graph.microsoft.com/.default';
        });
    }

    public function test_smtp_and_graph_tokens_do_not_share_a_cache_entry(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'token-abc', 'expires_in' => 3599]),
        ]);

        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');
        $this->service->accessToken('tenant-1', 'client-1', 'secret-1', MicrosoftOAuthTokenService::GRAPH_SCOPE);

        Http::assertSentCount(2);
    }

    public function test_it_reuses_the_cached_token_on_subsequent_calls(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'token-abc', 'expires_in' => 3599]),
        ]);

        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');
        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');

        Http::assertSentCount(1);
    }

    public function test_forget_forces_a_new_token_request(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'token-abc', 'expires_in' => 3599]),
        ]);

        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');
        $this->service->forget('tenant-1', 'client-1');
        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');

        Http::assertSentCount(2);
    }

    public function test_tokens_are_cached_per_app_registration(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'token-abc', 'expires_in' => 3599]),
        ]);

        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');
        $this->service->accessToken('tenant-2', 'client-1', 'secret-1');

        Http::assertSentCount(2);
    }

    public function test_a_token_expiring_within_the_skew_window_is_not_cached(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'token-abc', 'expires_in' => 60]),
        ]);

        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');
        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');

        Http::assertSentCount(2);
    }

    public function test_it_surfaces_the_microsoft_error_description(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'AADSTS7000215: Invalid client secret provided.',
            ], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/AADSTS7000215/');

        $this->service->accessToken('tenant-1', 'client-1', 'wrong-secret');
    }

    public function test_it_throws_when_the_response_carries_no_token(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['expires_in' => 3599]),
        ]);

        $this->expectException(RuntimeException::class);

        $this->service->accessToken('tenant-1', 'client-1', 'secret-1');
    }
}
