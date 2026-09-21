<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class GetIpInfoAction
{
    /**
     * Resolve geographic and network information for the given IP address.
     *
     * @return array{ip: string, location: string|null, country: string, region: string, city: string, latitude: string|null, longitude: string|null, isp: string, timezone: string}
     */
    public function execute(string $ip): array
    {
        if ($this->isLocalIp($ip)) {
            return [
                'ip' => $ip,
                'location' => 'Local Network',
                'country' => 'Local',
                'region' => 'Local',
                'city' => 'Local',
                'latitude' => null,
                'longitude' => null,
                'isp' => 'Local ISP',
                'timezone' => config('app.timezone'),
            ];
        }

        try {
            $response = Http::timeout(5)->get("https://ipinfo.io/{$ip}/json");

            if ($response->successful()) {
                $data = $response->json();

                if (! empty($data) && ! isset($data['error'])) {
                    $location = $data['loc'] ?? '';
                    [$latitude, $longitude] = ! empty($location) ? explode(',', $location) : [null, null];

                    return [
                        'ip' => $ip,
                        'location' => $location,
                        'country' => $data['country'] ?? 'Unknown',
                        'region' => $data['region'] ?? 'Unknown',
                        'city' => $data['city'] ?? 'Unknown',
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'isp' => $data['org'] ?? 'Unknown',
                        'timezone' => $data['timezone'] ?? 'Unknown',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('IP tracking error: '.$e->getMessage());
        }

        return [
            'ip' => $ip,
            'location' => null,
            'country' => 'Unknown',
            'region' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => null,
            'longitude' => null,
            'isp' => 'Unknown',
            'timezone' => 'Unknown',
        ];
    }

    private function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', 'localhost', '::1'], true)
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '192.168.');
    }
}
