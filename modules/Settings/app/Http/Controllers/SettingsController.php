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

        return redirect()->back()->with('success', 'Settings updated successfully');
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

        return redirect()->route('admin.settings.manage')->with('success', 'Settings synced successfully.');
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

        try {
            $action->create(
                $request->validated(),
                $request,
                $request->input('option_keys', []),
                $request->input('option_values', [])
            );

            return redirect()->route('admin.settings.manage')->with('success', 'Setting created successfully');
        } catch (Exception $e) {
            Log::error('Setting creation failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Failed to create setting');
        }
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

        try {
            $action->update(
                $setting,
                $request->validated(),
                $request,
                $request->input('option_keys', []),
                $request->input('option_values', [])
            );

            return redirect()->route('admin.settings.manage')->with('success', 'Setting updated successfully');
        } catch (Exception $e) {
            Log::error('Setting update failed', ['setting_id' => $setting->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Failed to update setting');
        }
    }

    /**
     * Remove the specified setting.
     */
    public function destroy(Setting $setting): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        $setting->delete();

        return redirect()->route('admin.settings.manage')->with('success', 'Setting deleted successfully');
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

        try {
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
        } catch (Exception $e) {
            Log::error('Settings export error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error exporting settings: '.$e->getMessage());
        }
    }

    /**
     * Import settings from JSON file.
     */
    public function import(ImportSettingsRequest $request, SettingsImportExportService $service): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        try {

            $jsonContent = file_get_contents($request->file('settings_file')->getPathname());
            $jsonContent = $service->sanitizeJsonContent($jsonContent);
            $settings = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON parsing error: '.json_last_error_msg().', content sample: '.substr($jsonContent, 0, 100));

                return redirect()->back()->with('error', 'Invalid JSON file: '.json_last_error_msg().'. Please check the file format.');
            }

            if (! is_array($settings) || empty($settings)) {
                return redirect()->back()->with('error', 'The JSON file must contain an array of settings.');
            }

            if (! isset($settings[0]['key'])) {
                if (isset($settings['data']) && is_array($settings['data'])) {
                    $settings = $settings['data'];
                } else {
                    return redirect()->back()->with('error', 'The JSON file does not contain valid settings format. Each setting must have at least a "key" property.');
                }
            }

            $validator = Validator::make(['settings' => $settings], [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string|max:255',
                'settings.*.group' => 'required|string|max:255',
                'settings.*.type' => 'required|string|in:text,textarea,file,image,integer,float,boolean,select,multi-select,array,json',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->with('error', 'Invalid settings format: '.$validator->errors()->first());
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

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
                    $errors[] = "Error importing setting '{$settingData['key']}': ".$e->getMessage();
                    Log::error("Settings import error for key {$settingData['key']}: ".$e->getMessage());
                }
            }

            $message = "Successfully imported {$imported} settings.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} existing settings.";
            }
            if (! empty($errors)) {
                $message .= ' Encountered '.count($errors).' errors.';
            }

            return redirect()->route('admin.settings.manage')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } catch (Exception $e) {
            Log::error('Settings import error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error importing settings: '.$e->getMessage());
        }
    }

    /**
     * Update multiple settings at once (bulk operations).
     */
    public function bulkUpdate(BulkUpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        try {
            $action = $request->validated('action');
            $ids = $request->validated('ids');

            if (empty($ids)) {
                return redirect()->back()->with('error', 'No settings selected');
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
                    ? "Made {$count} settings visible"
                    : "Made {$count} settings invisible";
            } else {
                $group = $request->validated('group');

                foreach ($settings as $setting) {
                    $setting->group = $group;
                    $setting->save();
                }

                $message = "Moved {$count} settings to the \"{$group}\" group";
            }

            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            Log::error('Bulk update error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error updating settings: '.$e->getMessage());
        }
    }

    /**
     * Delete multiple settings at once.
     */
    public function bulkDelete(BulkDeleteSettingsRequest $request): RedirectResponse
    {
        $this->authorize('developer', Setting::class);

        try {
            $ids = $request->validated('ids');

            if (empty($ids)) {
                return redirect()->back()->with('error', 'No settings selected');
            }

            $count = Setting::whereIn('id', $ids)->count();
            Setting::whereIn('id', $ids)->delete();

            return redirect()->back()->with('success', "{$count} settings deleted successfully");
        } catch (Exception $e) {
            Log::error('Bulk delete error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error deleting settings: '.$e->getMessage());
        }
    }
}
