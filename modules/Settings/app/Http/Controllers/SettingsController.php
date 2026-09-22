<?php

namespace Modules\Settings\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Settings\Actions\SaveSettingAction;
use Modules\Settings\Actions\SyncSettingsAction;
use Modules\Settings\Http\Requests\BulkDeleteSettingsRequest;
use Modules\Settings\Http\Requests\BulkUpdateSettingsRequest;
use Modules\Settings\Http\Requests\ImportSettingsRequest;
use Modules\Settings\Http\Requests\StoreSettingRequest;
use Modules\Settings\Http\Requests\UpdateSettingRequest;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\SettingsImportExportService;
use Mrj\Foundation\Http\Controllers\Controller;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Renderable
    {
        $this->authorize('editSystem', Setting::class);

        $settings = Setting::enabled()
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('group');

        return view('settings::index', compact('settings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SaveSettingAction $action): RedirectResponse
    {
        $this->authorize('editSystem', Setting::class);

        $action->saveAll($request);

        return redirect()->back()->with('success', __('settings::settings.flash.settings_updated'));
    }

    /**
     * Display a list of settings for management (create, edit, delete).
     */
    public function manage(): Renderable
    {
        $this->authorize('developer', Setting::class);

        $settings = Setting::orderBy('group')->orderBy('key')->get();

        return view('settings::manage', compact('settings'));
    }

    /**
     * Sync settings from module config/settings.php files into the database.
     */
    public function syncSettings(SyncSettingsAction $action): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $action->execute();

        return redirect()->route('admin.settings.manage')->with('success', __('settings::settings.flash.settings_synced'));
    }

    /**
     * Show the form for creating a new setting.
     */
    public function create(): Renderable
    {
        $this->authorize('developer', Setting::class);

        $groups = Setting::distinct('group')->pluck('group')->toArray();
        $types = ['text', 'textarea', 'encrypted', 'file', 'image', 'integer', 'float', 'boolean', 'select', 'multi-select', 'array', 'json'];

        return view('settings::create', compact('groups', 'types'));
    }

    /**
     * Store a newly created setting.
     */
    public function storeNew(StoreSettingRequest $request, SaveSettingAction $action): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $action->create(
            $request->validated(),
            $request,
            $request->input('option_keys', []),
            $request->input('option_values', [])
        );

        return redirect()->route('admin.settings.manage')->with('success', __('settings::settings.flash.setting_created'));
    }

    /**
     * Show the form for editing a setting.
     */
    public function edit(Setting $setting): Renderable
    {
        $this->authorize('developer', Setting::class);

        $groups = Setting::distinct('group')->pluck('group')->toArray();
        $types = ['text', 'textarea', 'encrypted', 'file', 'image', 'integer', 'float', 'boolean', 'select', 'multi-select', 'array', 'json'];

        return view('settings::edit', compact('setting', 'groups', 'types'));
    }

    /**
     * Update the specified setting.
     */
    public function update(UpdateSettingRequest $request, Setting $setting, SaveSettingAction $action): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $action->update(
            $setting,
            $request->validated(),
            $request,
            $request->input('option_keys', []),
            $request->input('option_values', [])
        );

        return redirect()->route('admin.settings.manage')->with('success', __('settings::settings.flash.setting_updated'));
    }

    /**
     * Remove the specified setting.
     */
    public function destroy(Setting $setting): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $setting->delete();

        return redirect()->route('admin.settings.manage')->with('success', __('settings::settings.flash.setting_deleted'));
    }

    /**
     * Show the import form.
     */
    public function importForm(): Renderable
    {
        $this->authorize('developer', Setting::class);

        return view('settings::import');
    }

    /**
     * Export settings to JSON file.
     */
    public function export(Request $request, SettingsImportExportService $service): Response|RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $group = $request->input('group');

        $query = Setting::query()
            ->select('id', 'key', 'group', 'type', 'description', 'is_visible', 'is_required', 'is_disabled', 'value', 'options')
            ->orderBy('group')
            ->orderBy('key');

        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get();
        $exportData = $service->buildExportData($settings);

        $filename = 'settings_export_'.date('Y-m-d_His').'.json';
        $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON encoding error: '.json_last_error_msg());
        }

        return response($jsonContent)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Import settings from JSON file.
     */
    public function import(ImportSettingsRequest $request, SettingsImportExportService $service): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $jsonContent = file_get_contents($request->file('settings_file')->getPathname());
        $jsonContent = $service->sanitizeJsonContent($jsonContent);
        $settings = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON parsing error: '.json_last_error_msg().', content sample: '.substr($jsonContent, 0, 100));

            return redirect()->back()->with('error', __('settings::settings.errors.invalid_json_file', ['error' => json_last_error_msg()]));
        }

        if (! is_array($settings) || empty($settings)) {
            return redirect()->back()->with('error', __('settings::settings.errors.json_must_be_array'));
        }

        if (! isset($settings[0]['key'])) {
            if (isset($settings['data']) && is_array($settings['data'])) {
                $settings = $settings['data'];
            } else {
                return redirect()->back()->with('error', __('settings::settings.errors.invalid_settings_format'));
            }
        }

        $validator = Validator::make(['settings' => $settings], [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.group' => 'required|string|max:255',
            'settings.*.type' => 'required|string|in:text,textarea,file,image,integer,float,boolean,select,multi-select,array,json',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', __('settings::settings.errors.invalid_settings_validation', ['error' => $validator->errors()->first()]));
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Per-row, deliberately: one malformed row's error is collected and
        // reported, not allowed to abort every other row's import.
        foreach ($settings as $settingData) {
            try {
                $existing = Setting::where('key', $settingData['key'])->first();

                if ($existing) {
                    if ($request->input('import_mode') === 'overwrite') {
                        $service->updateFromImport($existing, $settingData);
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $service->createFromImport($settingData);
                    $imported++;
                }
            } catch (Exception $e) {
                $errors[] = __('settings::settings.errors.import_row_error', ['key' => $settingData['key'], 'message' => $e->getMessage()]);
                Log::error("Settings import error for key {$settingData['key']}: ".$e->getMessage());
            }
        }

        $message = __('settings::settings.flash.import_success', ['count' => $imported]);
        if ($skipped > 0) {
            $message .= ' '.__('settings::settings.flash.import_skipped', ['count' => $skipped]);
        }
        if (! empty($errors)) {
            $message .= ' '.__('settings::settings.flash.import_errors_count', ['count' => count($errors)]);
        }

        return redirect()->route('admin.settings.manage')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    /**
     * Update multiple settings at once (bulk operations).
     */
    public function bulkUpdate(BulkUpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $action = $request->validated('action');
        $ids = $request->validated('ids');

        if (empty($ids)) {
            return redirect()->back()->with('error', __('settings::settings.flash.no_settings_selected'));
        }

        $settings = Setting::whereIn('id', $ids)->get();
        $count = count($settings);

        if ($action === 'visibility') {
            $visibility = (bool) $request->validated('visibility');

            foreach ($settings as $setting) {
                $setting->is_visible = $visibility;
                $setting->save();
            }

            $message = $visibility
                ? __('settings::settings.flash.made_visible', ['count' => $count])
                : __('settings::settings.flash.made_invisible', ['count' => $count]);
        } else {
            $group = $request->validated('group');

            foreach ($settings as $setting) {
                $setting->group = $group;
                $setting->save();
            }

            $message = __('settings::settings.flash.moved_to_group', ['count' => $count, 'group' => $group]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Delete multiple settings at once.
     */
    public function bulkDelete(BulkDeleteSettingsRequest $request): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $ids = $request->validated('ids');

        if (empty($ids)) {
            return redirect()->back()->with('error', __('settings::settings.flash.no_settings_selected'));
        }

        $count = Setting::whereIn('id', $ids)->count();
        Setting::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', __('settings::settings.flash.settings_deleted', ['count' => $count]));
    }
}
