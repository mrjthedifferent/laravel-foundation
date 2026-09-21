<?php

namespace Modules\ActivityLog\Traits;

use Illuminate\Support\Facades\Http;

trait IPTracker
{
    private string $url = 'http://ip-api.com/json/';

    /**
     * Best-effort geolocation of a caller's IP. Returns null when the lookup
     * fails; nothing here is important enough to fail a request over.
     *
     * @return array<string, mixed>|null
     */
    public function trackIP(string $ip): ?array
    {
        $response = Http::timeout(10)->connectTimeout(5)->get($this->url.$ip);

        return $response->successful() ? $response->json() : null;
    }
}
