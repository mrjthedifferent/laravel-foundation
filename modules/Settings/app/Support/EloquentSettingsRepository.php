<?php

declare(strict_types=1);

namespace Modules\Settings\Support;

use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Contracts\SettingsRepository;
use Override;

final class EloquentSettingsRepository implements SettingsRepository
{
    private function cacheKey(): string
    {
        return config('foundation.cache.prefix').'app_settings';
    }

    #[Override]
    public function all(): array
    {
        $settings = Cache::get($this->cacheKey());

        // A cache written before 0.11 holds Setting models. Laravel 13 hands those
        // back as __PHP_Incomplete_Class rather than unserializing them, so an app
        // upgrading with a warm cache would otherwise break on boot.
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
