@extends('settings::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.settings.manage') }}" class="breadcrumb-item">{{ __('settings::settings.import.breadcrumb_manage') }}</a>
    <span class="breadcrumb-item active">{{ __('settings::settings.import.breadcrumb_active') }}</span>
@endsection

@section('content')
<form action="{{ route('admin.settings.import') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <x-page-header
        title="{{ __('settings::settings.import.title') }}"
        subtitle="{{ __('settings::settings.import.subtitle') }}"
        icon="ph-upload"
        :back-url="route('admin.settings.manage')"
        back-label="{{ __('settings::settings.import.back') }}" />

    <x-form-section title="{{ __('settings::settings.import.section_how_it_works') }}" icon="ph-info">
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 sm:col-span-6">
                <div class="flex gap-2">
                    <span class="fd-icon-tile fd-icon-tile-sm">
                        <i class="ph-git-merge"></i>
                    </span>
                    <div>
                        <div class="font-semibold text-sm">{{ __('settings::settings.import.merge_mode_title') }}</div>
                        <div class="text-muted text-xs">{{ __('settings::settings.import.merge_mode_desc') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <div class="flex gap-2">
                    <span class="fd-icon-tile fd-icon-tile-sm is-danger">
                        <i class="ph-arrows-clockwise"></i>
                    </span>
                    <div>
                        <div class="font-semibold text-sm">{{ __('settings::settings.import.overwrite_mode_title') }}</div>
                        <div class="text-muted text-xs">{{ __('settings::settings.import.overwrite_mode_desc') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="{{ __('settings::settings.import.section_import_file') }}" icon="ph-brackets-curly">
        <div class="mb-4">
            <x-form.file name="settings_file" id="settings_file" accept=".json" required
                :label="__('settings::settings.import.json_file_label')"
                :help="__('settings::settings.import.json_file_help', ['ext' => '.json'])" />
        </div>
        <div>
            <label class="form-label font-semibold text-sm required">{{ __('settings::settings.import.import_mode_label') }}</label>
            <div class="grid grid-cols-12 gap-2">
                <div class="col-span-12 sm:col-span-6">
                    <label class="flex gap-2 p-4 border rounded-md cursor-pointer" id="mode-merge-label">
                        <input type="radio" name="import_mode" value="merge" class="mt-1 shrink-0" checked onchange="highlightMode()">
                        <div>
                            <div class="font-semibold text-sm">{{ __('settings::settings.import.mode_merge_title') }}</div>
                            <div class="text-muted text-xs">{{ __('settings::settings.import.mode_merge_desc') }}</div>
                        </div>
                    </label>
                </div>
                <div class="col-span-12 sm:col-span-6">
                    <label class="flex gap-2 p-4 border rounded-md cursor-pointer" id="mode-overwrite-label">
                        <input type="radio" name="import_mode" value="overwrite" class="mt-1 shrink-0" onchange="highlightMode()">
                        <div>
                            <div class="font-semibold text-sm">{{ __('settings::settings.import.mode_overwrite_title') }}</div>
                            <div class="text-muted text-xs">{{ __('settings::settings.import.mode_overwrite_desc') }}</div>
                        </div>
                    </label>
                </div>
            </div>
            @error('import_mode')<div class="text-danger mt-1 text-sm">{{ $message }}</div>@enderror
        </div>
    </x-form-section>

    <x-alert type="warning" icon="ph-warning" class="mb-6">
        <span class="text-sm">
            {!! __('settings::settings.import.backup_warning', [
                'link' => '<a href="'.route('admin.settings.export').'" class="font-semibold alert-link">'.__('settings::settings.import.export_current_settings').'</a>',
            ]) !!}
        </span>
    </x-alert>

    <div class="flex justify-between items-center">
        <a href="{{ route('admin.settings.manage') }}" class="btn btn-light">
            <i class="ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
        </a>
        <button type="submit" class="btn btn-primary px-6">
            <i class="ph-upload"></i>{{ __('settings::settings.import.submit') }}
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script>
function highlightMode() {
    var merge  = document.querySelector('[name=import_mode][value=merge]').checked;
    var mLabel = document.getElementById('mode-merge-label');
    var oLabel = document.getElementById('mode-overwrite-label');
    mLabel.style.borderColor = merge  ? 'var(--fd-accent)' : '';
    oLabel.style.borderColor = !merge ? 'var(--fd-danger)'  : '';
}
highlightMode();
</script>
@endpush
