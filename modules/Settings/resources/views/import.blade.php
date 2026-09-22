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
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="d-flex gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;">
                        <i class="ph-git-merge"></i>
                    </div>
                    <div>
                        <div class="fw-semibold fs-sm">{{ __('settings::settings.import.merge_mode_title') }}</div>
                        <div class="text-muted fs-xs">{{ __('settings::settings.import.merge_mode_desc') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="d-flex gap-2">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;">
                        <i class="ph-arrows-clockwise"></i>
                    </div>
                    <div>
                        <div class="fw-semibold fs-sm">{{ __('settings::settings.import.overwrite_mode_title') }}</div>
                        <div class="text-muted fs-xs">{{ __('settings::settings.import.overwrite_mode_desc') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="{{ __('settings::settings.import.section_import_file') }}" icon="ph-brackets-curly">
        <div class="mb-3">
            <label class="form-label fw-semibold fs-sm required">{{ __('settings::settings.import.json_file_label') }}</label>
            <input type="file"
                   class="form-control form-control-sm @error('settings_file') is-invalid @enderror"
                   name="settings_file" id="settings_file"
                   accept=".json" required>
            <div class="form-text">{!! __('settings::settings.import.json_file_help', ['ext' => '<code>.json</code>']) !!}</div>
            @error('settings_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="form-label fw-semibold fs-sm required">{{ __('settings::settings.import.import_mode_label') }}</label>
            <div class="row g-2">
                <div class="col-sm-6">
                    <label class="d-flex gap-2 p-3 border rounded cursor-pointer" id="mode-merge-label">
                        <input type="radio" name="import_mode" value="merge" class="mt-1 flex-shrink-0" checked onchange="highlightMode()">
                        <div>
                            <div class="fw-semibold fs-sm">{{ __('settings::settings.import.mode_merge_title') }}</div>
                            <div class="text-muted fs-xs">{{ __('settings::settings.import.mode_merge_desc') }}</div>
                        </div>
                    </label>
                </div>
                <div class="col-sm-6">
                    <label class="d-flex gap-2 p-3 border rounded cursor-pointer" id="mode-overwrite-label">
                        <input type="radio" name="import_mode" value="overwrite" class="mt-1 flex-shrink-0" onchange="highlightMode()">
                        <div>
                            <div class="fw-semibold fs-sm">{{ __('settings::settings.import.mode_overwrite_title') }}</div>
                            <div class="text-muted fs-xs">{{ __('settings::settings.import.mode_overwrite_desc') }}</div>
                        </div>
                    </label>
                </div>
            </div>
            @error('import_mode')<div class="text-danger mt-1 fs-sm">{{ $message }}</div>@enderror
        </div>
    </x-form-section>

    <x-alert type="warning" icon="ph-warning" class="mb-4">
        <span class="fs-sm">
            {!! __('settings::settings.import.backup_warning', [
                'link' => '<a href="'.route('admin.settings.export').'" class="fw-semibold alert-link">'.__('settings::settings.import.export_current_settings').'</a>',
            ]) !!}
        </span>
    </x-alert>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.settings.manage') }}" class="btn btn-outline-secondary">
            <i class="ph-x me-1"></i>{{ __('foundation::foundation.common.cancel') }}
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="ph-upload me-1"></i>{{ __('settings::settings.import.submit') }}
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
    mLabel.style.borderColor = merge  ? 'var(--bs-primary)' : '';
    oLabel.style.borderColor = !merge ? 'var(--bs-danger)'  : '';
}
highlightMode();
</script>
@endpush
