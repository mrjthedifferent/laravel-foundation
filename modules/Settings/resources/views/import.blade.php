@extends('settings::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.settings.manage') }}" class="breadcrumb-item">Manage Settings</a>
    <span class="breadcrumb-item active">Import Settings</span>
@endsection

@section('content')
<form action="{{ route('admin.settings.import') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <x-page-header
        title="Import Settings"
        subtitle="Restore settings from a JSON export file"
        icon="ph-upload"
        :back-url="route('admin.settings.manage')"
        back-label="Back" />

    <x-form-section title="How It Works" icon="ph-info">
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="d-flex gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;">
                        <i class="ph-git-merge"></i>
                    </div>
                    <div>
                        <div class="fw-semibold fs-sm">Merge mode</div>
                        <div class="text-muted fs-xs">Only adds new settings. Existing ones are left unchanged.</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="d-flex gap-2">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;">
                        <i class="ph-arrows-clockwise"></i>
                    </div>
                    <div>
                        <div class="fw-semibold fs-sm">Overwrite mode</div>
                        <div class="text-muted fs-xs">Replaces existing settings that share the same key.</div>
                    </div>
                </div>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Import File" icon="ph-brackets-curly">
        <div class="mb-3">
            <label class="form-label fw-semibold fs-sm required">JSON File</label>
            <input type="file"
                   class="form-control form-control-sm @error('settings_file') is-invalid @enderror"
                   name="settings_file" id="settings_file"
                   accept=".json" required>
            <div class="form-text">Only <code>.json</code> files exported from this system</div>
            @error('settings_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="form-label fw-semibold fs-sm required">Import Mode</label>
            <div class="row g-2">
                <div class="col-sm-6">
                    <label class="d-flex gap-2 p-3 border rounded cursor-pointer" id="mode-merge-label">
                        <input type="radio" name="import_mode" value="merge" class="mt-1 flex-shrink-0" checked onchange="highlightMode()">
                        <div>
                            <div class="fw-semibold fs-sm">Merge</div>
                            <div class="text-muted fs-xs">Add new, keep existing</div>
                        </div>
                    </label>
                </div>
                <div class="col-sm-6">
                    <label class="d-flex gap-2 p-3 border rounded cursor-pointer" id="mode-overwrite-label">
                        <input type="radio" name="import_mode" value="overwrite" class="mt-1 flex-shrink-0" onchange="highlightMode()">
                        <div>
                            <div class="fw-semibold fs-sm">Overwrite</div>
                            <div class="text-muted fs-xs">Replace matching keys</div>
                        </div>
                    </label>
                </div>
            </div>
            @error('import_mode')<div class="text-danger mt-1 fs-sm">{{ $message }}</div>@enderror
        </div>
    </x-form-section>

    <x-alert type="warning" icon="ph-warning" class="mb-4">
        <span class="fs-sm">
            <strong>Back up first.</strong> Importing settings can override existing configuration.
            <a href="{{ route('admin.settings.export') }}" class="fw-semibold alert-link">Export current settings</a> before proceeding.
        </span>
    </x-alert>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.settings.manage') }}" class="btn btn-outline-secondary">
            <i class="ph-x me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="ph-upload me-1"></i>Import Settings
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
