@extends('settings::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.settings.manage') }}" class="breadcrumb-item">{{ __('settings::settings.edit.breadcrumb_manage') }}</a>
    <span class="breadcrumb-item active">{{ __('settings::settings.edit.breadcrumb_active') }}</span>
@endsection

@section('content')
    <form action="{{ route('admin.settings.update', $setting) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')

        @php
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
            $tc = $typeColors[$setting->type] ?? 'secondary';
        @endphp

        <x-page-header title="{{ __('settings::settings.edit.title') }}" icon="ph-pencil-simple-line" :back-url="route('admin.settings.manage')" back-label="{{ __('settings::settings.edit.back') }}">
            <x-slot name="actions">
                <code
                    class="bg-primary bg-opacity-10 text-body rounded px-2 py-1 fs-xs fw-semibold">{{ $setting->key }}</code>
                <span class="badge bg-{{ $tc }}-subtle text-{{ $tc }} border border-{{ $tc }}-subtle text-uppercase fs-xs">{{ $setting->type }}</span>
            </x-slot>
        </x-page-header>

        {{-- Section 1: Identity --}}
        <x-form-section title="{{ __('settings::settings.edit.section_identity') }}" icon="ph-identification-card">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.edit.key_label') }}</label>
                    <input type="text" class="form-control form-control-sm bg-body-tertiary" value="{{ $setting->key }}" readonly disabled>
                    <div class="form-text">{{ __('settings::settings.edit.key_help') }}</div>
                </div>
                <div class="col-md-6">
                    <x-form.select
                        class="select"
                        name="existing_group"
                        id="existing_group"
                        label="{{ __('settings::settings.edit.group_label') }}"
                        required
                        :options="collect($groups)->mapWithKeys(fn($g) => [$g => $g])->put('__new__', __('settings::settings.edit.group_new_option'))->prepend('', '')->toArray()"
                        :selected="null"
                        data-placeholder="{{ __('settings::settings.edit.group_select_placeholder') }}"
                    />
                    <x-form.input name="group" id="group" :value="old('group', $setting->group)" class="mt-2 d-none" placeholder="{{ __('settings::settings.edit.group_new_placeholder') }}" />
                </div>
                <div class="col-md-6">
                    <x-form.select
                        class="select"
                        name="type"
                        id="type"
                        label="{{ __('settings::settings.edit.type_label') }}"
                        required
                        :options="collect($types)->mapWithKeys(fn($t) => [$t => ucfirst($t)])->prepend('', '')->toArray()"
                        :selected="$setting->type"
                        data-placeholder="{{ __('settings::settings.edit.type_select_placeholder') }}"
                    />
                </div>
                <div class="col-md-6">
                    <x-form.input name="description" label="{{ __('foundation::foundation.common.description') }}" :value="old('description', $setting->description)" placeholder="{{ __('settings::settings.edit.description_placeholder') }}" />
                </div>
            </div>
        </x-form-section>

        {{-- Section 2: Flags --}}
        <x-form-section title="{{ __('settings::settings.edit.section_flags') }}" icon="ph-toggle-right">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded border">
                        <div>
                            <div class="fw-semibold fs-sm">{{ __('settings::settings.edit.visible_title') }}</div>
                            <div class="text-muted fs-xs">{{ __('settings::settings.edit.visible_desc') }}</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible"
                                {{ $setting->is_visible ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded border">
                        <div>
                            <div class="fw-semibold fs-sm">{{ __('settings::settings.edit.required_title') }}</div>
                            <div class="text-muted fs-xs">{{ __('settings::settings.edit.required_desc') }}</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_required" name="is_required"
                                {{ $setting->is_required ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
        </x-form-section>

        {{-- Section 3: Current Value — card kept with id for JS toggling --}}
        <div id="value_container" class="card mb-3">
            <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
                <i class="ph-pencil-line text-primary"></i>
                <span class="fw-semibold text-uppercase fs-xs">{{ __('settings::settings.edit.value_header') }}</span>
            </div>
            <div class="card-body">
                <div id="value_text_container" class="{{ $setting->type === 'text' ? '' : 'd-none' }}">
                    <x-form.input name="value_text" :value="old('value_text', $setting->type === 'text' ? $setting->value : '')" />
                </div>
                <div id="value_textarea_container" class="{{ $setting->type === 'textarea' ? '' : 'd-none' }}">
                    <x-form.textarea name="value_textarea" :value="old('value_textarea', $setting->type === 'textarea' ? $setting->value : '')" :rows="4" />
                </div>
                <div id="value_encrypted_container" class="{{ $setting->type === 'encrypted' ? '' : 'd-none' }}">
                    <x-form.input type="password" name="value_encrypted" :placeholder="$setting->type === 'encrypted' ? __('settings::settings.edit.value_encrypted_placeholder_set') : __('settings::settings.edit.value_encrypted_placeholder_unset')" />
                </div>
                <div id="value_integer_container" class="{{ $setting->type === 'integer' ? '' : 'd-none' }}">
                    <x-form.input type="number" name="value_integer" :value="old('value_integer', $setting->type === 'integer' ? $setting->value : '')" />
                </div>
                <div id="value_float_container" class="{{ $setting->type === 'float' ? '' : 'd-none' }}">
                    <x-form.input type="number" name="value_float" :value="old('value_float', $setting->type === 'float' ? $setting->value : '')" step="0.01" />
                </div>
                <div id="value_boolean_container" class="{{ $setting->type === 'boolean' ? '' : 'd-none' }}">
                    <div class="d-flex align-items-center gap-3 p-3 border rounded">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="value_boolean" name="value_boolean"
                                {{ $setting->type === 'boolean' && $setting->value ? 'checked' : '' }}>
                        </div>
                        <label class="form-check-label fw-medium" for="value_boolean">{{ __('settings::settings.edit.value_boolean_label') }}</label>
                    </div>
                </div>
                <div id="value_file_container" class="{{ $setting->type === 'file' ? '' : 'd-none' }}">
                    @if ($setting->type === 'file' && $setting->value)
                        <a href="{{ $setting->value }}" target="_blank"
                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 mb-2">
                            <i class="ph-file-arrow-down"></i> {{ __('settings::settings.edit.view_current_file') }}
                        </a>
                    @endif
                    <input type="file" class="form-control form-control-sm" name="value_file">
                    <div class="form-text">{{ __('settings::settings.edit.leave_empty_file') }}</div>
                </div>
                <div id="value_image_container" class="{{ $setting->type === 'image' ? '' : 'd-none' }}">
                    @if ($setting->type === 'image' && $setting->value)
                        <img src="{{ $setting->value }}" alt="{{ $setting->key }}" id="img-preview"
                            class="img-thumbnail d-block mb-2" style="max-height:100px;max-width:220px;object-fit:contain;">
                    @else
                        <img src="" id="img-preview" class="img-thumbnail d-none mb-2"
                            style="max-height:100px;max-width:220px;object-fit:contain;">
                    @endif
                    <input type="file" class="form-control form-control-sm" name="value_image" id="value_image_input"
                        accept="image/*">
                    <div class="form-text">{{ __('settings::settings.edit.leave_empty_image') }}</div>
                </div>
                <div id="value_json_container" class="{{ $setting->type === 'json' ? '' : 'd-none' }}">
                    <div id="json-editor" class="border rounded" style="height:320px;"></div>
                    <x-form.input type="hidden" name="value_json" id="value_json" :value="old('value_json', $setting->type === 'json' ? (is_string($setting->value) ? $setting->value : json_encode($setting->value)) : '{}')" />
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-light border" id="format-json"><i
                                class="ph-brackets-curly me-1"></i>{{ __('settings::settings.edit.json_format') }}</button>
                        <button type="button" class="btn btn-sm btn-light border" id="validate-json"><i
                                class="ph-check me-1"></i>{{ __('settings::settings.edit.json_validate') }}</button>
                        <span id="json-validation-result" class="ms-1 fs-sm"></span>
                    </div>
                </div>
                <div id="value_select_container" class="{{ $setting->type === 'select' ? '' : 'd-none' }}">
                    @if ($setting->type === 'select' && is_array($setting->options) && count($setting->options))
                        <x-form.select class="select" name="value_select" id="value_select" :options="['' => ''] + $setting->options" :selected="$setting->value" data-placeholder="{{ __('settings::settings.edit.select_value_placeholder') }}" />
                    @else
                        <p class="text-muted mb-0 fs-sm"><i class="ph-info me-1"></i>{{ __('settings::settings.edit.no_options_defined') }}</p>
                    @endif
                </div>
                <div id="value_multi-select_container" class="{{ $setting->type === 'multi-select' ? '' : 'd-none' }}">
                    @if ($setting->type === 'multi-select' && is_array($setting->options) && count($setting->options))
                        <x-form.select class="select" name="value_multi-select[]" id="value_multi_select" multiple :options="$setting->options" :selected="is_array($setting->value) ? $setting->value : []" data-placeholder="{{ __('settings::settings.edit.select_options_placeholder') }}" />
                    @else
                        <p class="text-muted mb-0 fs-sm"><i class="ph-info me-1"></i>{{ __('settings::settings.edit.no_options_defined') }}</p>
                    @endif
                </div>
                <div id="value_array_container" class="{{ $setting->type === 'array' ? '' : 'd-none' }}">
                    <div id="array_values_list">
                        @if ($setting->type === 'array' && is_array($setting->value) && count($setting->value))
                            @foreach ($setting->value as $val)
                                <div class="input-group input-group-sm mb-2 array-value-row">
                                    <x-form.input name="value_array[]" :value="$val" />
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="removeArrayValue(this)"><i class="ph-trash"></i></button>
                                </div>
                            @endforeach
                        @else
                            <div class="input-group input-group-sm mb-2 array-value-row">
                                <x-form.input name="value_array[]" placeholder="{{ __('settings::settings.edit.array_value_placeholder') }}" />
                                <button type="button" class="btn btn-outline-danger" onclick="removeArrayValue(this)"><i
                                        class="ph-trash"></i></button>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addArrayValue()">
                        <i class="ph-plus me-1"></i>{{ __('settings::settings.edit.add_value') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Section 4: Options — card kept with id for JS toggling --}}
        <div id="options_container"
            class="card mb-3 {{ in_array($setting->type, ['select', 'multi-select']) ? '' : 'd-none' }}">
            <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="ph-list-bullets text-primary"></i>
                    <span class="fw-semibold text-uppercase fs-xs">{{ __('settings::settings.edit.section_options') }}</span>
                </div>
                <span class="text-muted fs-xs">{{ __('settings::settings.edit.options_badge') }}</span>
            </div>
            <div class="card-body">
                <div class="row g-0 mb-2 px-1">
                    <div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">{{ __('settings::settings.edit.options_col_key') }}</span></div>
                    <div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">{{ __('settings::settings.edit.options_col_label') }}</span></div>
                </div>
                <div id="options_list">
                    @if (in_array($setting->type, ['select', 'multi-select']) && is_array($setting->options) && count($setting->options))
                        @foreach ($setting->options as $optKey => $optVal)
                            <div class="row g-2 mb-2 align-items-center option-row">
                                <div class="col-5"><x-form.input name="option_keys[]" :value="$optKey" /></div>
                                <div class="col-5"><x-form.input name="option_values[]" :value="$optVal" /></div>
                                <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="removeOption(this)"><i class="ph-trash"></i></button></div>
                            </div>
                        @endforeach
                    @else
                        <div class="row g-2 mb-2 align-items-center option-row">
                            <div class="col-5"><x-form.input name="option_keys[]" placeholder="{{ __('settings::settings.edit.option_key_placeholder') }}" /></div>
                            <div class="col-5"><x-form.input name="option_values[]" placeholder="{{ __('settings::settings.edit.option_value_placeholder') }}" /></div>
                            <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="removeOption(this)"><i class="ph-trash"></i></button></div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addOption()">
                    <i class="ph-plus me-1"></i>{{ __('settings::settings.edit.add_option') }}
                </button>
            </div>
        </div>

        {{-- Footer actions --}}
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.settings.manage') }}" class="btn btn-outline-secondary">
                <i class="ph-x me-1"></i>{{ __('foundation::foundation.common.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="ph-floppy-disk me-1"></i>{{ __('settings::settings.edit.submit') }}
            </button>
        </div>

    </form>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/ace/ace.js') }}"></script>
    <script>
        $(document).ready(function() {
            var currentGroup = '{{ $setting->group }}';
            var groupInList = false;
            $('#existing_group option').each(function() {
                if ($(this).val() === currentGroup) {
                    groupInList = true;
                    return false;
                }
            });
            if (!groupInList && currentGroup) {
                $('#existing_group').val('__new__');
                $('#group').removeClass('d-none').val(currentGroup);
            } else {
                $('#group').val(currentGroup);
            }

            $('#existing_group').select2({
                width: '100%',
                placeholder: '{{ __('settings::settings.edit.group_select_placeholder') }}',
                allowClear: true
            });
            $('#existing_group').on('change', function() {
                var val = $(this).val();
                if (val === '__new__') {
                    $('#group').removeClass('d-none').focus();
                } else {
                    $('#group').addClass('d-none').val(val);
                }
            });

            $('#type').select2({
                width: '100%',
                placeholder: '{{ __('settings::settings.edit.type_select_placeholder') }}',
                allowClear: false
            });
            $('#type').on('change', function() {
                showValueField();
            });

            @if ($setting->type === 'select')
                $('#value_select').select2({
                    width: '100%',
                    placeholder: '{{ __('settings::settings.edit.select_value_placeholder') }}',
                    allowClear: true
                });
            @elseif ($setting->type === 'multi-select')
                $('#value_multi_select').select2({
                    width: '100%',
                    placeholder: '{{ __('settings::settings.edit.select_options_placeholder') }}',
                    allowClear: false
                });
            @endif

            $('#value_image_input').on('change', function() {
                var file = this.files[0];
                if (file && file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#img-preview').attr('src', e.target.result).removeClass('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });

            @if ($setting->type === 'json')
                initJsonEditor();
            @endif
        });

        function showValueField() {
            var type = document.getElementById('type').value;
            document.querySelectorAll('[id^="value_"][id$="_container"]').forEach(function(el) {
                el.classList.add('d-none');
            });
            document.getElementById('options_container').classList.add('d-none');
            document.getElementById('value_container').classList.toggle('d-none', !type);
            if (!type) return;
            var target = document.getElementById('value_' + type + '_container');
            if (target) target.classList.remove('d-none');
            if (type === 'select' || type === 'multi-select') document.getElementById('options_container').classList.remove(
                'd-none');
            if (type === 'json' && !window.jsonEditorInitialized) initJsonEditor();
        }

        function initJsonEditor() {
            var el = document.getElementById('json-editor');
            if (!el) return;
            var editor = ace.edit('json-editor');
            editor.setTheme('ace/theme/xcode');
            editor.session.setMode('ace/mode/json');
            editor.setOptions({
                showPrintMargin: false
            });
            var raw = document.getElementById('value_json').value || '{}';
            try {
                editor.setValue(JSON.stringify(JSON.parse(raw), null, 2), -1);
            } catch (e) {
                editor.setValue(raw, -1);
            }
            var timeout;
            editor.getSession().on('change', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    try {
                        document.getElementById('value_json').value = JSON.stringify(JSON.parse(editor
                            .getValue()));
                        document.getElementById('json-validation-result').innerHTML =
                            '<span class="text-success"><i class="ph-check me-1"></i>{{ __('settings::settings.edit.json_valid') }}</span>';
                    } catch (e) {
                        document.getElementById('json-validation-result').innerHTML =
                            '<span class="text-danger">' + e.message + '</span>';
                    }
                }, 300);
            });
            document.getElementById('format-json').addEventListener('click', function() {
                try {
                    editor.setValue(JSON.stringify(JSON.parse(editor.getValue()), null, 2), -1);
                } catch (e) {
                    window.showConfirm?.({
                        icon: 'error',
                        title: '{{ __('settings::settings.edit.invalid_json_title') }}',
                        text: e.message,
                        confirmClass: 'btn btn-danger',
                        showCancelButton: false,
                        confirmText: '{{ __('settings::settings.common.ok') }}'
                    });
                }
            });
            document.getElementById('validate-json').addEventListener('click', function() {
                try {
                    JSON.parse(editor.getValue());
                    document.getElementById('json-validation-result').innerHTML =
                        '<span class="text-success"><i class="ph-check me-1"></i>{{ __('settings::settings.edit.json_valid_json') }}</span>';
                } catch (e) {
                    document.getElementById('json-validation-result').innerHTML = '<span class="text-danger">' + e
                        .message + '</span>';
                }
            });
            document.querySelector('form').addEventListener('submit', function(e) {
                try {
                    document.getElementById('value_json').value = JSON.stringify(JSON.parse(editor.getValue()));
                } catch (err) {
                    e.preventDefault();
                    window.showConfirm?.({
                        icon: 'error',
                        title: '{{ __('settings::settings.edit.invalid_json_title') }}',
                        text: err.message,
                        confirmClass: 'btn btn-danger',
                        showCancelButton: false,
                        confirmText: '{{ __('settings::settings.common.ok') }}'
                    });
                }
            });
            window.jsonEditorInitialized = true;
        }

        function addOption() {
            var row = '<div class="row g-2 mb-2 align-items-center option-row">' +
                '<div class="col-5"><input type="text" class="form-control form-control-sm" name="option_keys[]" placeholder="{{ __('settings::settings.edit.option_key_placeholder') }}"></div>' +
                '<div class="col-5"><input type="text" class="form-control form-control-sm" name="option_values[]" placeholder="{{ __('settings::settings.edit.option_value_placeholder') }}"></div>' +
                '<div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOption(this)"><i class="ph-trash"></i></button></div></div>';
            document.getElementById('options_list').insertAdjacentHTML('beforeend', row);
        }

        function removeOption(btn) {
            if (document.querySelectorAll('.option-row').length > 1) btn.closest('.option-row').remove();
        }

        function addArrayValue() {
            var row =
                '<div class="input-group input-group-sm mb-2 array-value-row"><input type="text" class="form-control" name="value_array[]" placeholder="{{ __('settings::settings.edit.array_value_placeholder') }}"><button type="button" class="btn btn-outline-danger" onclick="removeArrayValue(this)"><i class="ph-trash"></i></button></div>';
            document.getElementById('array_values_list').insertAdjacentHTML('beforeend', row);
        }

        function removeArrayValue(btn) {
            btn.closest('.array-value-row').remove();
        }
    </script>
@endpush
