<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mrj\Foundation\Services\FileManagerService;
use OwenIt\Auditing\Auditable;

class Setting extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable;

    protected static function booted()
    {
        static::saved(function () {
            self::flush();
        });

        static::deleted(function () {
            self::flush();
        });
    }

    /**
     * Drop the cache AND signal long-running queue workers to restart.
     *
     * SettingsServiceProvider copies settings into config() once, at boot. Forgetting the cache
     * cannot change the in-memory config of a process that has already booted, so a worker that
     * started before a setting was edited kept serving the old value until the next deploy — a
     * notification switched off in the UI carried on sending from the queue. queue:restart just
     * stamps a timestamp workers check between jobs, so it is cheap and safe to call repeatedly.
     */
    private static bool $queueRestartSignalled = false;

    private static function flush(): void
    {
        Cache::forget('app_settings');

        // Once per process: the notification settings page writes a row per notification per
        // channel, and restarting on each would boot the command hundreds of times in one
        // request. The signal is a single timestamp, so one call covers the whole save.
        if (self::$queueRestartSignalled) {
            return;
        }

        self::$queueRestartSignalled = true;

        try {
            Artisan::call('queue:restart');
        } catch (\Throwable $e) {
            Log::warning('Settings saved but queue:restart failed; workers may serve stale settings.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'group',
        'value',
        'type',
        'options',
        'values',
        'description',
        'is_visible',
        'is_required',
        'is_editable',
    ];

    protected $appends = [
        'required',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'required' => 'boolean',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Legacy compatibility method to maintain backward compatibility
     * with code that relies on the "required" attribute
     */
    public function getRequiredAttribute(): bool
    {
        if (isset($this->attributes['is_required'])) {
            return (bool) $this->attributes['is_required'];
        }

        return ! ($this->key === 'sms_gateway' || $this->key === 'email_mailer');
    }

    public function getOptionsAttribute($options)
    {
        if ($this->key === 'sms_gateway') {
            $smsGateways = getSystemSetting('sms_gateways');
            $gateways = [];

            // Check if $smsGateways is an array before trying to loop through it
            if (is_array($smsGateways) || (is_string($smsGateways) && json_validate($smsGateways))) {
                // If it's a JSON string, decode it first
                if (is_string($smsGateways)) {
                    $smsGateways = json_decode($smsGateways, true);
                }

                if (is_array($smsGateways)) {
                    foreach ($smsGateways as $gateway) {
                        if (is_array($gateway) && isset($gateway['TYPE'])) {
                            $gateways[$gateway['TYPE']] = $gateway['TYPE'];
                        }
                    }
                }
            }

            return $gateways;
        }

        if ($this->key === 'email_mailer') {
            $emailMailers = getSystemSetting('email_mailers');
            $mailers = [];

            // Check if $emailMailers is an array before trying to loop through it
            if (is_array($emailMailers) || (is_string($emailMailers) && json_validate($emailMailers))) {
                // If it's a JSON string, decode it first
                if (is_string($emailMailers)) {
                    $emailMailers = json_decode($emailMailers, true);
                }

                if (is_array($emailMailers)) {
                    foreach ($emailMailers as $mailer) {
                        if (is_array($mailer) && isset($mailer['TYPE'])) {
                            $mailers[$mailer['TYPE']] = $mailer['TYPE'];
                        }
                    }
                }
            }

            return $mailers;
        }

        // Handle JSON decoding safely
        try {
            return json_decode($options, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::warning("Failed to decode options JSON for setting '{$this->key}': ".$e->getMessage());

            return is_string($options) ? $options : [];
        }
    }

    public function getValueAttribute($value)
    {
        try {
            return match ((string) $this->type) {
                'boolean' => (bool) $value,
                'integer' => (int) $value,
                'float' => (float) $value,
                'array', 'multi-select' => ! empty($value) ? explode(',', $value) : [],
                'json' => $this->getJsonValue($value),
                'image' => FileManagerService::getImage($value),
                'file' => FileManagerService::getFile($value),
                default => $value,
            };
        } catch (\JsonException $e) {
            Log::warning("Failed to decode JSON value for setting '{$this->key}': ".$e->getMessage());

            return $value; // Return the raw value if JSON decoding fails
        }
    }

    /**
     * Helper method to safely decode JSON values
     */
    protected function getJsonValue($value)
    {
        // Return empty object for null or empty values
        if (empty($value) || $value === 'null') {
            return [];
        }

        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }

        // Handle string values
        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

                return $decoded;
            } catch (\JsonException $e) {
                Log::warning("Invalid JSON value for setting '{$this->key}': ".$e->getMessage());

                return [];
            }
        }

        // For any other type, return an empty array
        return [];
    }

    public function setValueAttribute($value)
    {
        try {
            $this->attributes['value'] = match ((string) $this->type) {
                'array', 'multi-select' => is_array($value) ? implode(',', $value) : $value,
                'json' => $this->setJsonValue($value),
                default => $value,
            };
        } catch (\JsonException $e) {
            Log::warning("Failed to encode JSON value for setting '{$this->key}': ".$e->getMessage());
            $this->attributes['value'] = is_string($value) ? $value : json_encode([]);
        }
    }

    /**
     * Helper method to safely encode JSON values
     */
    protected function setJsonValue($value)
    {
        // If it's already a JSON string, validate it
        if (is_string($value) && json_validate($value)) {
            return $value;
        }

        // Convert arrays or objects to JSON strings
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // For null or empty values, return empty object
        if (empty($value) || $value === 'null') {
            return '{}';
        }

        // For any other value, try to encode as is
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_visible', true);
    }
}
