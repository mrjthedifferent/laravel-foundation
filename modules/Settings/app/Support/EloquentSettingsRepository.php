<?php

declare(strict_types=1);

namespace Modules\Settings\Support;

use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Support\Tenancy;
use Override;

final class EloquentSettingsRepository implements SettingsRepository
{
    private function cacheKey(): string
    {
        return Tenancy::cacheKey('app_settings');
    }

    #[Override]
    public function all(): array
    {
        $settings = Cache::get($this->cacheKey());

        // Anything but an array (a cache miss, or a corrupt or foreign value under
        // this key) is rebuilt from the database.
        if (is_array($settings)) {
            return $settings;
        }

        $settings = Setting::all()
            ->map(fn (Setting $setting): array => [
                'key' => $setting->key,
                'value' => $setting->value,
                'group' => $setting->group,
                'type' => $setting->type,
                'description' => $setting->description,
            ])
            ->all();

        Cache::forever($this->cacheKey(), $settings);

        return $settings;
    }

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return collect($this->all())->firstWhere('key', $key)['value'] ?? $default;
    }

    #[Override]
    public function fresh(string $key, mixed $default = null): mixed
    {
        return Setting::where('key', $key)->first()->value ?? $default;
    }

    #[Override]
    public function forget(): void
    {
        Cache::forget($this->cacheKey());
    }
}
