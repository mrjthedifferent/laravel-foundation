<?php

namespace Modules\Settings\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Models\Setting;

final class SettingsImportExportService
{
    /**
     * Build export data array from a collection of settings (stdClass objects from query).
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildExportData(Collection $settings): array
    {
        $exportData = [];

        foreach ($settings as $setting) {
            $data = [
                'key' => $setting->key,
                'group' => $setting->group,
                'type' => $setting->type,
                'description' => $setting->description,
                'is_visible' => (bool) $setting->is_visible,
                'is_required' => (bool) $setting->is_required,
                'is_disabled' => (bool) $setting->is_disabled,
            ];

            $value = $setting->value;

            if ($setting->type === 'json') {
                $data['value'] = $this->normalizeJsonValue($value, $setting->key);
            } elseif ($setting->type === 'array' || $setting->type === 'multi-select') {
                $data['value'] = $this->normalizeArrayValue($value, $setting->key);
            } else {
                $data['value'] = $value;
            }

            if (! empty($setting->options)) {
                $data['options'] = $this->normalizeOptions($setting->options, $setting->key);
            }

            $exportData[] = $data;
        }

        return $exportData;
    }

    /**
     * Create a new Setting from import data.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFromImport(array $data): Setting
    {
        $setting = new Setting;
        $setting->key = $data['key'];
        $setting->group = $data['group'];
        $setting->type = $data['type'];
        $setting->description = $data['description'] ?? null;
        $setting->is_visible = $data['is_visible'] ?? true;
        $setting->is_required = $data['is_required'] ?? true;
        $setting->is_disabled = $data['is_disabled'] ?? false;

        if (isset($data['options'])) {
            $setting->options = is_string($data['options']) ? $data['options'] : json_encode($data['options']);
        }

        if ($data['type'] === 'json' && isset($data['value'])) {
            $setting->value = is_string($data['value']) ? $data['value'] : json_encode($data['value']);
        } else {
            $setting->value = $data['value'] ?? null;
        }

        $setting->save();

        return $setting;
    }

    /**
     * Update an existing Setting from import data.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateFromImport(Setting $setting, array $data): Setting
    {
        $setting->group = $data['group'];
        $setting->type = $data['type'];

        if (isset($data['description'])) {
            $setting->description = $data['description'];
        }
        if (isset($data['is_visible'])) {
            $setting->is_visible = $data['is_visible'];
        }
        if (isset($data['is_required'])) {
            $setting->is_required = $data['is_required'];
        }
        if (isset($data['is_disabled'])) {
            $setting->is_disabled = $data['is_disabled'];
        }

        if (isset($data['options'])) {
            $setting->options = is_string($data['options']) ? $data['options'] : json_encode($data['options']);
        }

        if (isset($data['value'])) {
            if ($data['type'] === 'json') {
                $setting->value = is_string($data['value']) ? $data['value'] : json_encode($data['value']);
            } else {
                $setting->value = $data['value'];
            }
        }

        $setting->save();

        return $setting;
    }

    /**
     * Try to fix common JSON syntax issues without altering string values.
     * Do not strip // or /* — they can appear inside values (e.g. URLs) and would corrupt the JSON.
     */
    public function sanitizeJsonContent(string $content): string
    {
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Remove trailing commas in arrays and objects (invalid in JSON)
        $content = preg_replace('/,\s*([\]}])/m', '$1', $content);

        // Escape unescaped control characters inside double-quoted strings
        $content = $this->escapeControlCharsInJsonStrings($content);

        return $content;
    }

    /**
     * Escape control characters (e.g. literal newlines) inside JSON double-quoted string values only.
     */
    private function escapeControlCharsInJsonStrings(string $content): string
    {
        $length = strlen($content);
        $result = '';
        $inDoubleQuotedString = false;
        $escapeNext = false;
        $i = 0;

        while ($i < $length) {
            $c = $content[$i];

            if ($escapeNext) {
                $result .= $c;
                $escapeNext = false;
                $i++;

                continue;
            }

            if ($c === '\\' && $inDoubleQuotedString) {
                $result .= $c;
                $escapeNext = true;
                $i++;

                continue;
            }

            if ($c === '"' && ! $inDoubleQuotedString) {
                $inDoubleQuotedString = true;
                $result .= $c;
                $i++;

                continue;
            }

            if ($c === '"' && $inDoubleQuotedString) {
                $inDoubleQuotedString = false;
                $result .= $c;
                $i++;

                continue;
            }

            if ($inDoubleQuotedString && ord($c) < 32) {
                $result .= match ($c) {
                    "\n" => '\\n',
                    "\r" => '\\r',
                    "\t" => '\\t',
                    default => ' ',
                };
                $i++;

                continue;
            }

            $result .= $c;
            $i++;
        }

        return $result;
    }

    private function normalizeJsonValue(mixed $value, string $key): string
    {
        try {
            if (is_array($value)) {
                return json_encode($value);
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE ? json_encode($decoded) : $value;
            }

            return json_encode($value);
        } catch (Exception $e) {
            Log::warning("Setting {$key} failed to decode JSON value: ".$e->getMessage());

            return is_string($value) ? $value : json_encode([]);
        }
    }

    private function normalizeArrayValue(mixed $value, string $key): string
    {
        try {
            if (is_array($value)) {
                return implode(',', $value);
            }

            if (is_string($value)) {
                if (json_validate($value)) {
                    $arrayValue = json_decode($value, true);

                    return is_array($arrayValue) ? implode(',', $arrayValue) : $value;
                }

                return $value;
            }

            return (string) $value;
        } catch (Exception $e) {
            Log::warning("Setting {$key} failed to process array value: ".$e->getMessage());

            return is_string($value) ? $value : '';
        }
    }

    private function normalizeOptions(mixed $options, string $key): string
    {
        try {
            if (is_array($options)) {
                return json_encode($options);
            }

            if (is_string($options)) {
                $decoded = json_decode($options, true);

                return json_last_error() === JSON_ERROR_NONE ? $options : json_encode(['value' => $options]);
            }

            return json_encode($options);
        } catch (Exception $e) {
            Log::warning("Setting {$key} options couldn't be processed: ".$e->getMessage());

            return json_encode(['error' => 'Invalid options data']);
        }
    }
}
