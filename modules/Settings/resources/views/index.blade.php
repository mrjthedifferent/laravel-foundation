@extends('settings::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('settings::settings.index.breadcrumb') }}</span>
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
    <div id="save-bar" class="hidden mb-4 sticky top-0 z-10">
        <div class="alert alert-warning flex items-center justify-between py-2 px-4 mb-0 rounded-none border-s-0 border-e-0">
            <span><i class="ph-warning-circle me-2"></i>{!! __('settings::settings.index.unsaved_changes') !!}</span>
            <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="ph-floppy-disk"></i>{{ __('settings::settings.index.save_now') }}
            </button>
        </div>
    </div>

    <x-page-header title="{{ __('settings::settings.index.title') }}" subtitle="{{ __('settings::settings.index.subtitle') }}" icon="ph-gear">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary">
                <i class="ph-floppy-disk"></i>{{ __('settings::settings.index.save_changes') }}
            </button>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-body p-0">
            <div class="grid grid-cols-12 gap-0">

                {{-- ── Sidebar ── --}}
                <div class="col-span-12 border-e bg-subtle md:col-span-3 xl:col-span-2">
                    <div class="p-2">
                        <div class="fd-overline px-2 pt-2 pb-1">
                            {{ __('settings::settings.index.categories') }}
                        </div>
                        <nav class="nav flex-col gap-1 nav-pills" id="settingsTabs" role="tablist">
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
                            <a class="nav-link flex items-center gap-1 py-2 px-2 text-sm {{ $loop->first ? 'active' : '' }}"
                                id="{{ snakeCase($tabKey) }}-tab"
                                data-fd-toggle="tab"
                                href="#{{ snakeCase($tabKey) }}"
                                role="tab">
                                <i class="{{ $tabIcon }}"></i>
                                <span class="whitespace-nowrap">{{ display_label($tabKey) }}</span>
                                @if($visibleCount > 0)
                                <span class="badge badge-count ms-auto">{{ $visibleCount }}</span>
                                @endif
                            </a>
                            @endforeach
                        </nav>
                    </div>
                </div>

                {{-- ── Tab content ── --}}
                <div class="col-span-12 md:col-span-9 xl:col-span-10">
                    <div class="tab-content p-6" id="settingsTabsContent">
                        @foreach ($settings as $tabKey => $group)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                            id="{{ snakeCase($tabKey) }}"
                            role="tabpanel">

                            {{-- Section header --}}
                            <div class="flex items-center gap-2 mb-4 pb-2 border-b">
                                <span class="fd-icon-tile fd-icon-tile-sm">
                                    <i class="{{ $tabIcon ?? 'ph-sliders' }}"></i>
                                </span>
                                <div>
                                    <div class="fd-overline">{{ display_label($tabKey) }}</div>
                                    @php $tabCount = count($group); @endphp
                                    <div class="text-muted text-xs">{{ trans_choice('settings::settings.index.setting_count', $tabCount) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 items-start gap-4">
                                @foreach ($group as $settingKey => $setting)
                                @php
                                $label = __(ucwords(str_replace('_', ' ', $setting->key)));
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

                                <div class="{{ $isWide ? 'col-span-12 md:col-span-12' : 'col-span-12 xl:col-span-6 md:col-span-6' }}">
                                    <div class="card h-full">
                                        <div class="card-body py-2 px-4">

                                            {{-- Card label row --}}
                                            <div class="flex items-center gap-1 mb-2">
                                                <span class="font-semibold text-sm">{{ $label }}</span>
                                                <span class="badge ms-auto badge-{{ $badgeColor }} uppercase">{{ $setting->type }}</span>
                                            </div>

                                            {{-- ── Boolean ── --}}
                                            @if ($setting->type === 'boolean')
                                            <div class="flex items-center justify-between gap-4">
                                                <span class="text-muted text-sm">
                                                    {{ $setting->value ? __('settings::settings.index.currently_enabled') : __('settings::settings.index.currently_disabled') }}
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
                                            <div class="border rounded-md p-2 text-center relative overflow-hidden cursor-pointer" id="upload_box_{{ $setting->key }}">
                                                @if ($setting->value)
                                                <img src="{{ $setting->value }}"
                                                    id="preview_{{ $setting->key }}"
                                                    class="img-preview block mx-auto mb-1"
                                                    alt="{{ $label }}">
                                                <div class="text-muted text-xs"><i class="ph-pencil me-1"></i>{{ __('settings::settings.index.click_to_change_image') }}</div>
                                                @else
                                                <img src="" id="preview_{{ $setting->key }}"
                                                    class="img-preview hidden" alt="{{ $label }}">
                                                <i class="ph-image-square text-[2rem] text-muted block mb-1"></i>
                                                <div class="text-muted text-xs">{{ __('settings::settings.index.click_to_upload_image') }}</div>
                                                @endif
                                                <input type="file"
                                                    name="{{ $setting->key }}"
                                                    id="{{ $setting->key }}"
                                                    class="settings-input image-preview-input absolute top-0 start-0 w-full h-full opacity-0"
                                                    accept="image/*"
                                                    data-preview="preview_{{ $setting->key }}"
                                                    class="cursor-pointer">
                                            </div>

                                            {{-- ── File ── --}}
                                            @elseif ($setting->type === 'file')
                                            <div class="mt-1">
                                                @if ($setting->value)
                                                <a href="{{ $setting->value }}" target="_blank"
                                                    class="btn btn-light btn-sm mb-2">
                                                    <i class="ph-file-arrow-down"></i>{{ __('settings::settings.index.view_current_file') }}
                                                </a>
                                                @endif
                                                <input type="file" name="{{ $setting->key }}" id="{{ $setting->key }}"
                                                    class="form-control form-control-sm settings-input">
                                                <div class="form-text">{{ __('settings::settings.index.leave_empty_file') }}</div>
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
                                                data-placeholder="{{ __('settings::settings.index.select_option_placeholder') }}"
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
                                                data-placeholder="{{ __('settings::settings.index.select_options_placeholder') }}"
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
                                                class="form-control form-control-sm settings-input font-mono text-xs"
                                                rows="5">{{ is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT) : $setting->value }}</textarea>

                                            {{-- ── Encrypted ── --}}
                                            @elseif ($setting->type === 'encrypted')
                                            <input type="password"
                                                name="{{ $setting->key }}"
                                                id="{{ $setting->key }}"
                                                class="form-control form-control-sm settings-input"
                                                autocomplete="new-password"
                                                placeholder="{{ __('settings::settings.index.leave_blank_secret') }}"
                                                {{ $setting->required ? 'data-required' : '' }}>

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
                                            <div class="form-text mt-1">{{ display_label($setting->description) }}</div>
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
        <div class="card-footer flex justify-between items-center">
            <span class="text-muted text-sm"><i class="ph-info me-1"></i>{{ __('settings::settings.index.footer_note') }}</span>
            <button type="submit" class="btn btn-primary px-12">
                <i class="ph-floppy-disk"></i>{{ __('settings::settings.index.save_changes') }}
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

        $('#settingsTabs a[data-fd-toggle="tab"]').on('fd:tab-shown', function(e) {
            localStorage.setItem('activeSettingsTab', $(e.target).attr('href'));
        });

        // ── Unsaved-changes bar ──
        var formChanged = false;

        function markChanged() {
            if (!formChanged) {
                formChanged = true;
                $('#save-bar').removeClass('hidden');
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
                window.toast?.('warning', '{{ __('settings::settings.index.toast_required_title') }}', '{{ __('settings::settings.index.toast_required_text') }}');
                return false;
            }

            formChanged = false;
            $('#save-bar').addClass('hidden');
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
                    $img.attr('src', e.target.result).removeClass('hidden');
                    $img.closest('.border').find('.text-muted').html('<i class="ph-pencil me-1"></i>{{ __('settings::settings.index.click_to_change_image') }}');
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