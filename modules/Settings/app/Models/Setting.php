<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use JsonException;
use Modules\Settings\Contracts\SecretCipher;
use Modules\Settings\Database\Factories\SettingFactory;
use Modules\Settings\Events\SettingsUpdated;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Services\FileManagerService;
use Override;
use OwenIt\Auditing\Auditable;

class Setting extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable, HasFactory;

    /**
     * HasFactory's default guess is Database\Factories\{model}Factory in the
     * app namespace; this package's factories live per-module instead.
     */
    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    #[Override]
    protected static function booted()
    {
        static::saved(function (): void {
            self::flush();
        });

        static::deleted(function (): void {
            self::flush();
        });
    }

    /**
     * Drop the cache and let RestartQueueWorkers (listening for
     * SettingsUpdated) decide whether to signal queue workers to restart.
     */
    private static function flush(): void
    {
        app(SettingsRepository::class)->forget();

        event(new SettingsUpdated);
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
    #[Override]
    public function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Whether the setting must have a value, as a short `required` attribute
     * for the settings views and the serialized model. Falls back to the
     * column's default (true) when is_required was not loaded or set.
     */
    public function getRequiredAttribute(): bool
    {
        return (bool) ($this->attributes['is_required'] ?? true);
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
        } catch (JsonException $e) {
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
                'encrypted' => app(SecretCipher::class)->decrypt($value),
                default => $value,
            };
        } catch (JsonException $e) {
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
            } catch (JsonException $e) {
                Log::warning("Invalid JSON value for setting '{$this->key}': ".$e->getMessage());

                return [];
            }
        }

        // For any other type, return an empty array
        return [];
    }

    public function setValueAttribute($value): void
    {
        try {
            $this->attributes['value'] = match ((string) $this->type) {
                'array', 'multi-select' => is_array($value) ? implode(',', $value) : $value,
                'json' => $this->setJsonValue($value),
                'encrypted' => app(SecretCipher::class)->encrypt($value),
                default => $value,
            };
        } catch (JsonException $e) {
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

    /**
     * Redact encrypted settings out of the audit trail. The raw `value`
     * column already holds ciphertext for these rows, but auditing also
     * records the mutated attribute through the accessor in some code paths,
     * so both old and new values are replaced rather than left to chance.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        if ($this->type !== 'encrypted') {
            return $data;
        }

        if (array_key_exists('value', $data['old_values'] ?? [])) {
            $data['old_values']['value'] = '[REDACTED]';
        }

        if (array_key_exists('value', $data['new_values'] ?? [])) {
            $data['new_values']['value'] = '[REDACTED]';
        }

        return $data;
    }
}
