<?php

namespace Modules\User\Services;

/**
 * Parses a raw User-Agent string into device type, browser, and platform.
 *
 * Extracted from TrackLoginAction so that user-agent detection is a
 * single-responsibility utility that can be independently tested or swapped
 * for a package (e.g. jenssegers/agent) without touching the login-tracking logic.
 */
final readonly class UserAgentParser
{
    /**
     * Detect device category: 'tablet', 'mobile', or 'desktop'
     */
    public function detectDeviceType(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'unknown';
        }

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser name
     */
    public function detectBrowser(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'unknown';
        }

        if (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        }
        if (str_contains($userAgent, 'OPR') || str_contains($userAgent, 'Opera')) {
            return 'Opera';
        }
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }
        if (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident')) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    /**
     * Detect operating system / platform
     */
    public function detectPlatform(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'unknown';
        }

        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/macintosh|mac os x/i', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/windows|win32/i', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Other';
    }
}
