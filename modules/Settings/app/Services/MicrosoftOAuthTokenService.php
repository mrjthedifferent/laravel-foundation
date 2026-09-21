<?php

namespace Modules\Settings\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Issues Microsoft Entra access tokens for SMTP AUTH (XOAUTH2) using the
 * OAuth 2.0 client credentials grant.
 *
 * Requires an Entra app with the "Office 365 Exchange Online > SMTP.SendAsApp"
 * application permission (admin consented), an Exchange service principal
 * registered via New-ServicePrincipal, and Add-MailboxPermission on the
 * sending mailbox.
 *
 * @see https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth
 */
final readonly class MicrosoftOAuthTokenService
{
    /**
     * Scope for SMTP AUTH (XOAUTH2) against Exchange Online.
     */
    public const SMTP_SCOPE = 'https://outlook.office365.com/.default';

    /**
     * Scope for the Microsoft Graph sendMail API.
     */
    public const GRAPH_SCOPE = 'https://graph.microsoft.com/.default';

    private const CACHE_PREFIX = 'microsoft_smtp_token:';

    /**
     * Seconds shaved off the reported lifetime so a token never expires
     * mid-send, which would otherwise fail a whole queued batch.
     */
    private const EXPIRY_SKEW = 300;

    /**
     * Resolve a bearer token for the given app registration and scope, using the
     * cached one when it is still valid. The scope selects the resource — SMTP
     * ({@see self::SMTP_SCOPE}) or Graph ({@see self::GRAPH_SCOPE}) — and is part
     * of the cache key so the two never collide.
     *
     * @throws RuntimeException when Microsoft rejects the credentials
     */
    public function accessToken(string $tenantId, string $clientId, string $clientSecret, string $scope = self::SMTP_SCOPE): string
    {
        $cacheKey = $this->cacheKey($tenantId, $clientId, $scope);

        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json(), $response->status()));
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Microsoft returned no access token for the SMTP OAuth2 request.');
        }

        $lifetime = (int) $response->json('expires_in', 3600) - self::EXPIRY_SKEW;

        if ($lifetime > 0) {
            Cache::put($cacheKey, $token, now()->addSeconds($lifetime));
        }

        return $token;
    }

    /**
     * Drop the cached token so the next send re-authenticates. Called after the
     * credentials are edited, so a corrected secret takes effect immediately
     * instead of waiting out the previous token's lifetime.
     */
    public function forget(string $tenantId, string $clientId, string $scope = self::SMTP_SCOPE): void
    {
        Cache::forget($this->cacheKey($tenantId, $clientId, $scope));
    }

    /**
     * The SMTP scope keeps its original key so previously issued tokens survive
     * this method gaining a scope; only non-default scopes get a suffix.
     */
    private function cacheKey(string $tenantId, string $clientId, string $scope): string
    {
        $suffix = $scope === self::SMTP_SCOPE ? '' : '|'.$scope;

        return self::CACHE_PREFIX.sha1($tenantId.'|'.$clientId.$suffix);
    }

    /**
     * Surface Microsoft's own error text — the AADSTS code is the only thing
     * that makes a misconfiguration diagnosable (7000215 = wrong secret,
     * 900023 = wrong tenant, 65001 = admin consent missing).
     *
     * @param  array<string, mixed>|null  $body
     */
    private function errorMessage(?array $body, int $status): string
    {
        $description = $body['error_description'] ?? $body['error'] ?? null;

        return is_string($description) && $description !== ''
            ? 'Microsoft rejected the SMTP OAuth2 token request: '.$description
            : "Microsoft rejected the SMTP OAuth2 token request (HTTP {$status}).";
    }
}
