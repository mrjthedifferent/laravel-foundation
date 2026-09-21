<?php

namespace Modules\Settings\Actions;

use Illuminate\Http\Request;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Services\FileManagerService;

final readonly class SaveSettingAction
{
    /**
     * Persist all visible settings submitted from the system settings form.
     */
    public function saveAll(Request $request): void
    {
        Setting::enabled()
            ->get()
            ->each(fn (Setting $setting) => $this->saveSingleFromRequest($setting, $request));
    }

    /**
     * Create a new Setting from validated form data.
     */
    public function create(array $validated, Request $request, array $optionKeys = [], array $optionValues = []): Setting
    {
        $setting = new Setting;
        $setting->key = $validated['key'];

        return $this->fill($setting, $validated, $request, $optionKeys, $optionValues);
    }

    /**
     * Update an existing Setting from validated form data.
     */
    public function update(Setting $setting, array $validated, Request $request, array $optionKeys = [], array $optionValues = []): Setting
    {
        return $this->fill($setting, $validated, $request, $optionKeys, $optionValues);
    }

    /**
     * Apply validated data (from create/edit form) to a Setting and save it.
     */
    private function fill(Setting $setting, array $validated, Request $request, array $optionKeys, array $optionValues): Setting
    {
        $setting->group = $validated['group'];
        $setting->type = $validated['type'];
        $setting->description = $validated['description'] ?? null;
        $setting->is_visible = $validated['is_visible'];
        $setting->is_required = $validated['is_required'];

        $this->fillOptions($setting, $validated, $optionKeys, $optionValues);
        $this->fillValue($setting, $validated, $request);

        $setting->save();

        return $setting;
    }

    /**
     * Persist a single setting value from the system settings index form.
     * Uses the setting key directly as the request input name.
     */
    private function saveSingleFromRequest(Setting $setting, Request $request): void
    {
        match ($setting->type) {
            'file', 'image' => $this->saveFileByKey($setting, $request),
            'boolean' => $this->saveBooleanByKey($setting, $request),
            'multi-select' => $this->saveMultiSelectByKey($setting, $request),
            'json', 'array' => $this->saveJsonOrArrayByKey($setting, $request),
            default => $this->saveScalarByKey($setting, $request),
        };
    }

    /**
     * Build and assign the options JSON for select / multi-select types.
     */
    private function fillOptions(Setting $setting, array $validated, array $optionKeys, array $optionValues): void
    {
        if (! in_array($validated['type'], ['select', 'multi-select'])) {
            return;
        }

        $options = [];
        foreach ($optionKeys as $index => $key) {
            if (! empty($key) && isset($optionValues[$index])) {
                $options[$key] = $optionValues[$index];
            }
        }

        $setting->options = json_encode($options);
    }

    /**
     * Assign the value for create/edit forms (value is keyed as "value_{type}").
     */
    private function fillValue(Setting $setting, array $validated, Request $request): void
    {
        $type = $validated['type'];

        match (true) {
            in_array($type, ['file', 'image']) => $this->fillFileValue($setting, $type, $request),
            $type === 'boolean' => $setting->value = isset($validated['value_boolean']) ? '1' : '0',
            $type === 'multi-select' => $setting->value = $request->input('value_multi-select', []),
            in_array($type, ['json', 'array']) => $setting->value = $request->filled("value_{$type}")
                ? $request->input("value_{$type}")
                : ($setting->exists ? $setting->getRawOriginal('value') : '[]'),
            // A blank password field means "keep the current secret", not "erase it";
            // browsers never prefill password inputs, so blank is the common case on edit.
            $type === 'encrypted' => $setting->value = $request->filled('value_encrypted')
                ? $request->input('value_encrypted')
                : ($setting->exists ? $setting->getRawOriginal('value') : ''),
            default => $setting->value = $request->input("value_{$type}", $validated["value_{$type}"] ?? ''),
        };
    }

    // ── Helpers for saveAll (key = setting->key) ──────────────────────────────

    /** Upload a new file/image; skip if nothing submitted. */
    private function saveFileByKey(Setting $setting, Request $request): void
    {
        if (! $request->hasFile($setting->key)) {
            return;
        }

        $path = FileManagerService::uploadFile(
            $request->file($setting->key),
            $setting->value ?? null,
            'settings'
        );

        $setting->value = getUrlFromPath($path);
        $setting->save();
    }

    /** Save boolean from hidden-0 + optional checkbox-1 pattern. */
    private function saveBooleanByKey(Setting $setting, Request $request): void
    {
        $newValue = (bool) $request->input($setting->key, 0);

        if ($setting->value !== $newValue) {
            $setting->value = $newValue;
            $setting->save();
        }
    }

    /** Save multi-select array; always persist so deselecting all clears the value. */
    private function saveMultiSelectByKey(Setting $setting, Request $request): void
    {
        $newValue = $request->input($setting->key, []);
        $setting->value = is_array($newValue) ? $newValue : [];
        $setting->save();
    }

    /** Save any scalar type; skip entirely if key absent from request or value is null. */
    private function saveScalarByKey(Setting $setting, Request $request): void
    {
        if (! $request->has($setting->key) || is_null($request->input($setting->key))) {
            return;
        }

        // A blank password field means "keep the current secret", not "erase it";
        // browsers never prefill password inputs, so blank is the common case.
        if ($setting->type === 'encrypted' && $request->input($setting->key) === '') {
            return;
        }

        $setting->value = $request->input($setting->key);
        $setting->save();
    }

    /**
     * Save a json/array type setting.
     * Preserves the existing value when the submitted content is empty or missing,
     * preventing accidental data wipeout of complex structured settings.
     */
    private function saveJsonOrArrayByKey(Setting $setting, Request $request): void
    {
        if (! $request->has($setting->key)) {
            return;
        }

        $submitted = $request->input($setting->key);

        // Treat null or blank submission as "no change" — keep existing value
        if (is_null($submitted) || trim((string) $submitted) === '') {
            return;
        }

        $setting->value = $submitted;
        $setting->save();
    }

    // ── Helper for fill (key = "value_{type}") ────────────────────────────────

    /** Upload file/image for create/edit form; skip if nothing submitted. */
    private function fillFileValue(Setting $setting, string $type, Request $request): void
    {
        if (! $request->hasFile("value_{$type}")) {
            return;
        }

        $path = FileManagerService::uploadFile(
            $request->file("value_{$type}"),
            $setting->value ?? null,
            'settings'
        );

        $setting->value = getUrlFromPath($path);
    }
}
