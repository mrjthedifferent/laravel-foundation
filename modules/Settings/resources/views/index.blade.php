@extends('settings::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">System Settings</span>
@endsection

@section('content')
@php
// Sort categories: 'General' first, then alphabetical (A-Z)
$settings = collect($settings)->sortBy(function ($group, $tabKey) {
return strtolower($tabKey) === 'general' ? '000_general' : strtolower($tabKey);
});
@endphp
<form action="{{ route('admin.settings.store') }}" method="POST" enctype="multipart/form-data" id="settings-form" novalidate>
    @csrf

    {{-- ── Sticky unsaved-changes bar ── --}}
    <div id="save-bar" class="d-none mb-3 sticky-top" style="z-index:1020;">
        <div class="alert alert-warning d-flex align-items-center justify-content-between py-2 px-3 mb-0 rounded-0 border-start-0 border-end-0">
            <span><i class="ph-warning-circle me-2"></i>You have <strong>unsaved changes</strong> — remember to save.</span>
            <button type="submit" class="btn btn-dark btn-sm px-3">
                <i class="ph-floppy-disk me-1"></i> Save Now
            </button>
        </div>
    </div>

    <div class="card shadow-sm">

        {{-- ── Card header ── --}}
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                    <i class="ph-gear"></i>
                </div>
                <div>
                    <div class="fw-bold">System Settings</div>
                    <div class="text-muted fs-xs">Configure your application preferences</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="ph-floppy-disk me-1"></i> Save Changes
            </button>
        </div>

        <div class="card-body p-0">
            <div class="row g-0" style="min-height:520px;">

                {{-- ── Sidebar ── --}}
                <div class="col-md-2 border-end bg-body-tertiary">
                    <div class="p-2">
                        <div class="text-muted px-2 pt-2 pb-1 fs-xs fw-bold text-uppercase" style="letter-spacing:.06em;">
                            Categories
                        </div>
                        <nav class="nav flex-column gap-1 nav-pills" id="settingsTabs" role="tablist">
                            @foreach ($settings as $tabKey => $group)
                            @php
                            $tabKeyLower = strtolower($tabKey);
                            $tabIcon = 'ph-sliders';
                            if (str_contains($tabKeyLower, 'mail') || str_contains($tabKeyLower, 'email')) $tabIcon = 'ph-envelope';
                            elseif (str_contains($tabKeyLower, 'sms')) $tabIcon = 'ph-chat-teardrop-text';
                            elseif (str_contains($tabKeyLower, 'otp')) $tabIcon = 'ph-lock-key';
                            elseif (str_contains($tabKeyLower, 'payment')) $tabIcon = 'ph-credit-card';
                            elseif (str_contains($tabKeyLower, 'social') || str_contains($tabKeyLower, 'auth')) $tabIcon = 'ph-users-three';
                            elseif (str_contains($tabKeyLower, 'firebase')) $tabIcon = 'ph-fire';
                            elseif (str_contains($tabKeyLower, 'storage') || str_contains($tabKeyLower, 'file')) $tabIcon = 'ph-folder-open';
                            elseif (str_contains($tabKeyLower, 'notif')) $tabIcon = 'ph-bell';
                            elseif (str_contains($tabKeyLower, 'security') || str_contains($tabKeyLower, 'permission')) $tabIcon = 'ph-shield-check';
                            elseif (str_contains($tabKeyLower, 'contact')) $tabIcon = 'ph-address-book';
                            elseif (str_contains($tabKeyLower, 'mobile') || str_contains($tabKeyLower, 'app')) $tabIcon = 'ph-device-mobile';
                            elseif (str_contains($tabKeyLower, 'general')) $tabIcon = 'ph-house-line';
                            elseif (str_contains($tabKeyLower, 'api')) $tabIcon = 'ph-plug';

                            $visibleCount = count($group);
                            @endphp
                            <a class="nav-link d-flex align-items-center gap-1 py-2 px-2 fs-sm {{ $loop->first ? 'active' : '' }}"
                                id="{{ snakeCase($tabKey) }}-tab"
                                data-bs-toggle="pill"
                                href="#{{ snakeCase($tabKey) }}"
                                role="tab">
                                <i class="{{ $tabIcon }}"></i>
                                <span class="text-nowrap">{{ $tabKey }}</span>
                                @if($visibleCount > 0)
                                <span class="badge rounded-pill ms-auto bg-black bg-opacity-10 text-body fs-xs">{{ $visibleCount }}</span>
                                @endif
                            </a>
                            @endforeach
                        </nav>
                    </div>
                </div>

                {{-- ── Tab content ── --}}
                <div class="col-md-10">
                    <div class="tab-content p-4" id="settingsTabsContent">
                        @foreach ($settings as $tabKey => $group)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                            id="{{ snakeCase($tabKey) }}"
                            role="tabpanel">

                            {{-- Section header --}}
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-2">
                                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                                    <i class="{{ $tabIcon ?? 'ph-sliders' }}"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-uppercase fs-xs" style="letter-spacing:.04em;">{{ $tabKey }}</div>
                                    @php $tabCount = count($group); @endphp
                                    <div class="text-muted fs-xs">{{ $tabCount }} {{ Str::plural('setting', $tabCount) }}</div>
                                </div>
                            </div>

                            <div class="row g-3">
                                @foreach ($group as $settingKey => $setting)
                                @php
                                $label = ucwords(str_replace('_', ' ', $setting->key));
                                $isWide = in_array($setting->type, ['textarea','json','multi-select','array']);
                                $typeColors = [
                                'text' => 'secondary',
                                'textarea' => 'secondary',
                                'integer' => 'info',
                                'float' => 'info',
                                'boolean' => 'success',
                                'select' => 'primary',
                                'multi-select' => 'primary',
                                'image' => 'warning',
                                'file' => 'warning',
                                'json' => 'danger',
                                'array' => 'danger',
                                ];
                                $badgeColor = $typeColors[$setting->type] ?? 'secondary';
                                @endphp

                                <div class="{{ $isWide ? 'col-md-12' : 'col-xl-6 col-md-6' }}">
                                    <div class="card h-100">
                                        <div class="card-body py-2 px-3">

                                            {{-- Card label row --}}
                                            <div class="d-flex align-items-center gap-1 mb-2">
                                                <span class="fw-semibold fs-sm">{{ $label }}</span>
                                                <span class="badge ms-auto bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle fs-xs text-uppercase">{{ $setting->type }}</span>
                                            </div>

                                            {{-- ── Boolean ── --}}
                                            @if ($setting->type === 'boolean')
                                            <div class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="text-muted fs-sm">
                                                    {{ $setting->value ? 'Currently enabled' : 'Currently disabled' }}
                                                </span>
                                                <div class="form-check form-switch mb-0">
                                                    <input type="hidden" name="{{ $setting->key }}" value="0">
                                                    <input type="checkbox"
                                                        class="form-check-input settings-input"
                                                        name="{{ $setting->key }}"
                                                        id="{{ $setting->key }}"
                                                        value="1"
                                                        {{ $setting->value ? 'checked' : '' }}>
                                                </div>
                                            </div>

                                            {{-- ── Image ── --}}
                                            @elseif ($setting->type === 'image')
                                            <div class="border rounded p-2 text-center position-relative overflow-hidden cursor-pointer" id="upload_box_{{ $setting->key }}">
                                                @if ($setting->value)
                                                <img src="{{ $setting->value }}"
                                                    id="preview_{{ $setting->key }}"
                                                    class="img-preview d-block mx-auto mb-1"
                                                    alt="{{ $label }}">
                                                <div class="text-muted fs-xs"><i class="ph-pencil me-1"></i>Click to change image</div>
                                                @else
                                                <img src="" id="preview_{{ $setting->key }}"
                                                    class="img-preview d-none" alt="{{ $label }}">
                                                <i class="ph-image-square fs-2 text-muted d-block mb-1"></i>
                                                <div class="text-muted fs-xs">Click to upload image</div>
                                                @endif
                                                <input type="file"
                                                    name="{{ $setting->key }}"
                                                    id="{{ $setting->key }}"
                                                    class="settings-input image-preview-input position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                                    accept="image/*"
                                                    data-preview="preview_{{ $setting->key }}"
                                                    class="cursor-pointer">
                                            </div>

                                            {{-- ── File ── --}}
                                            @elseif ($setting->type === 'file')
                                            <div class="mt-1">
                                                @if ($setting->value)
                                                <a href="{{ $setting->value }}" target="_blank"
                                                    class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 mb-2">
                                                    <i class="ph-file-arrow-down"></i> View Current File
                                                </a>
                                                @endif
                                                <input type="file" name="{{ $setting->key }}" id="{{ $setting->key }}"
                                                    class="form-control form-control-sm settings-input">
                                                <div class="form-text">Leave empty to keep current file.</div>
                                            </div>

                                            {{-- ── Integer / Float ── --}}
                                            @elseif ($setting->type === 'integer' || $setting->type === 'float')
                                            <input type="number"
                                                step="{{ $setting->type === 'float' ? '0.01' : '1' }}"
                                                name="{{ $setting->key }}"
                                                id="{{ $setting->key }}"
                                                class="form-control form-control-sm settings-input"
                                                {{ $setting->required ? 'data-required' : '' }}
                                                value="{{ $setting->value }}">

                                            {{-- ── Textarea ── --}}
                                            @elseif ($setting->type === 'textarea')
                                            <textarea name="{{ $setting->key }}"
                                                id="{{ $setting->key }}"
                                                class="form-control form-control-sm settings-input"
                                                rows="3"
                                                {{ $setting->required ? 'data-required' : '' }}>{{ $setting->value }}</textarea>

                                            {{-- ── Select ── --}}
                                            @elseif ($setting->type === 'select')
                                            <select name="{{ $setting->key }}"
                                                id="{{ $setting->key }}"
                                                class="form-control form-control-sm select settings-input"
                                                data-placeholder="Select an option..."
                                                {{ $setting->required ? 'data-required' : '' }}>
                                                <option value=""></option>
                                                @foreach ($setting->options as $optionKey => $optionValue)
                                                <option value="{{ $optionKey }}"
                                                    {{ $setting->value == $optionKey ? 'selected' : '' }}>
                                                    {{ $optionValue }}
                                                </option>
                                                @endforeach
                                            </select>

                                            {{-- ── Multi-select ── --}}
                                            @elseif ($setting->type === 'multi-select')
                                            <select name="{{ $setting->key }}[]"
                                                id="{{ $setting->key }}"
                                                class="form-control form-control-sm select settings-input"
                                                data-placeholder="Select options..."
                                                multiple
                                                {{ $setting->required ? 'data-required' : '' }}>
                                                @foreach ($setting->options as $optionKey => $optionValue)
                                                <option value="{{ $optionKey }}"
                                                    {{ in_array($optionKey, $setting->value ?: []) ? 'selected' : '' }}>
                                                    {{ $optionValue }}
                                                </option>
                                                @endforeach
                                            </select>

                                            {{-- ── JSON ── --}}
                                            @elseif ($setting->type === 'json')
                                            <textarea name="{{ $setting->key }}"
                                                id="{{ $setting->key }}"
                                                class="form-control form-control-sm settings-input font-monospace"
                                                rows="5"
                                                style="font-size:.78rem;">{{ is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT) : $setting->value }}</textarea>

                                            {{-- ── Text (default) ── --}}
                                            @else
                                            <input type="text"
                                                name="{{ $setting->key }}"
                                                id="{{ $setting->key }}"
                                                class="form-control form-control-sm settings-input"
                                                {{ $setting->required ? 'data-required' : '' }}
                                                value="{{ $setting->value }}">
                                            @endif

                                            {{-- Description --}}
                                            @if ($setting->description)
                                            <div class="form-text mt-1">{{ $setting->description }}</div>
                                            @endif

                                        </div>
                                    </div>
                                </div>

                                @endforeach
                            </div>

                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Footer ── --}}
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted fs-sm"><i class="ph-info me-1"></i>Changes apply immediately after saving.</span>
            <button type="submit" class="btn btn-primary px-5">
                <i class="ph-floppy-disk me-1"></i> Save Changes
            </button>
        </div>

    </div>
</form>
@endsection



@push('scripts')
<script>
    $(document).ready(function() {
        // ── Persist Active Tab ──
        var activeTab = localStorage.getItem('activeSettingsTab');
        if (activeTab) {
            var $tab = $('#settingsTabs a[href="' + activeTab + '"]');
            if ($tab.length) {
                $tab.tab('show');
            }
        }

        $('#settingsTabs a[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
            localStorage.setItem('activeSettingsTab', $(e.target).attr('href'));
        });

        // ── Unsaved-changes bar ──
        var formChanged = false;

        function markChanged() {
            if (!formChanged) {
                formChanged = true;
                $('#save-bar').removeClass('d-none');
            }
        }
        $('#settings-form').on('change input', '.settings-input', markChanged);
        $('#settings-form').on('select2:select select2:unselect', 'select.select', markChanged);

        // ── Custom required validation ──
        $('#settings-form').on('submit', function(e) {
            var firstErrorTab = null;
            var hasError = false;

            $('[data-required]', this).each(function() {
                var $el = $(this);
                var val = $el.val();
                var empty = (val === null || val === '' || (Array.isArray(val) && val.length === 0));

                if (empty) {
                    hasError = true;
                    $el.addClass('is-invalid');
                    var $pane = $el.closest('.tab-pane');
                    if ($pane.length && !firstErrorTab) firstErrorTab = $pane.attr('id');
                } else {
                    $el.removeClass('is-invalid');
                }
            });

            if (hasError) {
                e.preventDefault();
                e.stopPropagation();
                if (firstErrorTab) $('#settingsTabs a[href="#' + firstErrorTab + '"]').tab('show');
                window.toast?.('warning', 'Required fields missing', 'Please fill in all required fields before saving.');
                return false;
            }

            formChanged = false;
            $('#save-bar').addClass('d-none');
        });

        // Clear invalid state on input
        $('#settings-form').on('input change', '[data-required]', function() {
            if ($(this).val()) $(this).removeClass('is-invalid');
        });

        // ── Image preview ──
        $(document).on('change', '.image-preview-input', function() {
            var file = this.files[0];
            var previewId = $(this).data('preview');
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var $img = $('#' + previewId);
                    $img.attr('src', e.target.result).removeClass('d-none');
                    $img.closest('.border').find('.text-muted').html('<i class="ph-pencil me-1"></i>Click to change image');
                    $img.closest('.border').find('.ph-image-square').hide();
                };
                reader.readAsDataURL(file);
            }
        });

        // ── Leave-page warning ──
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
</script>
@endpush