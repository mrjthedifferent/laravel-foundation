<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mrj\Foundation\Models\User;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (RFC 6238) for the foundation's two-factor authentication: secrets,
 * the QR code an authenticator app scans, code checks and recovery codes.
 *
 * A code is accepted once: the time step it matched is remembered per user,
 * so a code seen over someone's shoulder cannot be replayed within its window.
 *
 * @api
 */
final class TwoFactorAuthenticator
{
    private readonly Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        return array_map(
            fn (): string => Str::lower(Str::random(5).'-'.Str::random(5)),
            range(1, max(1, (int) config('foundation.two_factor.recovery_codes', 8))),
        );
    }

    /**
     * The otpauth:// URL for the user's authenticator app.
     */
    public function otpauthUrl(User $user, string $secret): string
    {
        $issuer = (string) (config('foundation.two_factor.issuer') ?: appName());

        return $this->engine->getQRCodeUrl($issuer, (string) ($user->email ?? $user->phone ?? $user->uuid), $secret);
    }

    /**
     * An inline SVG of the otpauth:// URL.
     */
    public function qrCodeSvg(User $user, string $secret): string
    {
        $svg = (new Writer(new ImageRenderer(
            new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(17, 24, 39))),
            new SvgImageBackEnd,
        )))->writeString($this->otpauthUrl($user, $secret));

        return trim(substr($svg, (int) strpos($svg, "\n") + 1)); // drop the XML declaration
    }

    /**
     * Checks a 6-digit code against the user's secret, once.
     */
    public function verify(User $user, ?string $code): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);

        if ($user->two_factor_secret === null || ! preg_match('/^\d{6}$/', (string) $code)) {
            return false;
        }

        $key = Tenancy::cacheKey('two_factor:step:'.$user->uuid);
        $lastStep = Cache::get($key);

        // Given a last step (0 when none), the matched step comes back, and it must be newer.
        $step = $this->engine->verifyKeyNewer(
            $user->two_factor_secret,
            (string) $code,
            is_int($lastStep) ? $lastStep : 0,
            (int) config('foundation.two_factor.window', 1),
        );

        if (! is_int($step)) {
            return false;
        }

        Cache::put($key, $step, now()->addMinutes(10));

        return true;
    }

    /**
     * Uses up one recovery code: true when it was one of the user's, and it is
     * then removed so it cannot be used again.
     */
    public function useRecoveryCode(User $user, ?string $code): bool
    {
        $code = Str::lower(trim((string) $code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $candidate) {
            if ($code !== '' && hash_equals($candidate, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}
