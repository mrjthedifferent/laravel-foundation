@extends('settings::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.settings.manage') }}" class="breadcrumb-item">{{ __('settings::settings.create.breadcrumb_manage') }}</a>
    <span class="breadcrumb-item active">{{ __('settings::settings.create.breadcrumb_active') }}</span>
@endsection

@section('content')
    <form action="{{ route('admin.settings.store_new') }}" method="POST" enctype="multipart/form-data" id="create-form">
        @csrf

        <x-page-header title="{{ __('settings::settings.create.title') }}" subtitle="{{ __('settings::settings.create.subtitle') }}" icon="ph-plus"
            :back-url="route('admin.settings.manage')" back-label="{{ __('settings::settings.create.back') }}" />

        <x-form-section title="{{ __('settings::settings.create.section_identity') }}" icon="ph-identification-card">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-6">
                    <label class="form-label font-semibold text-sm required">{{ __('settings::settings.create.key_label') }}</label>
                    <input type="text" class="form-control form-control-sm @error('key') is-invalid @enderror"
                        name="key" id="key" value="{{ old('key') }}" placeholder="{{ __('settings::settings.create.key_placeholder') }}" required>
                    <div class="form-text">{{ __('settings::settings.create.key_help') }}</div>
                    @error('key')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-span-12 md:col-span-6">
                    <x-form.select
                        class="select"
                        name="existing_group"
                        id="existing_group"
                        label="{{ __('settings::settings.create.group_label') }}"
                        :options="collect($groups)->mapWithKeys(fn($g) => [$g => $g])->put('__new__', __('settings::settings.create.group_new_option'))->prepend('', '')->toArray()"
                        :selected="null"
                        data-placeholder="{{ __('settings::settings.create.group_select_placeholder') }}"
                    />
                    <x-form.input name="group" id="group" :value="old('group')" class="mt-2 hidden" placeholder="{{ __('settings::settings.create.group_new_placeholder') }}" />
                </div>
                <div class="col-span-12 md:col-span-6">
                    <x-form.select
                        class="select"
                        name="type"
                        id="type"
                        label="{{ __('settings::settings.create.type_label') }}"
                        required
                        :options="collect($types)->mapWithKeys(fn($t) => [$t => ucfirst($t)])->prepend('', '')->toArray()"
                        :selected="old('type')"
                        data-placeholder="{{ __('settings::settings.create.type_select_placeholder') }}"
                    />
                </div>
                <div class="col-span-12 md:col-span-6">
                    <x-form.input name="description" id="description" label="{{ __('foundation::foundation.common.description') }}" :value="old('description')" placeholder="{{ __('settings::settings.create.description_placeholder') }}" />
                </div>
            </div>
        </x-form-section>

        <x-form-section title="{{ __('settings::settings.create.section_flags') }}" icon="ph-toggle-right">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-6">
                    <div class="flex items-center justify-between p-4 rounded-md border">
                        <div>
                            <div class="font-semibold text-sm">{{ __('settings::settings.create.visible_title') }}</div>
                            <div class="text-muted text-xs">{{ __('settings::settings.create.visible_desc') }}</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" checked>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <div class="flex items-center justify-between p-4 rounded-md border">
                        <div>
                            <div class="font-semibold text-sm">{{ __('settings::settings.create.required_title') }}</div>
                            <div class="text-muted text-xs">{{ __('settings::settings.create.required_desc') }}</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_required" name="is_required" checked>
                        </div>
                    </div>
                </div>
            </div>
        </x-form-section>

        <div class="card mb-4 hidden" id="value_container">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-pencil-line"></i></span>
                <span class="fd-overline">{{ __('settings::settings.create.value_header') }}</span>
            </div>
            <div class="card-body">
                <div id="value_text_container" class="hidden">
                    <x-form.input name="value_text" id="value_text" :value="old('value_text')" placeholder="{{ __('settings::settings.create.value_text_placeholder') }}" />
                </div>
                <div id="value_textarea_container" class="hidden">
                    <x-form.textarea name="value_textarea" id="value_textarea" :value="old('value_textarea')" :rows="4" placeholder="{{ __('settings::settings.create.value_textarea_placeholder') }}" />
                </div>
                <div id="value_encrypted_container" class="hidden">
                    <x-form.input type="password" name="value_encrypted" id="value_encrypted" placeholder="{{ __('settings::settings.create.value_encrypted_placeholder') }}" />
                </div>
                <div id="value_integer_container" class="hidden">
                    <x-form.input type="number" name="value_integer" id="value_integer" :value="old('value_integer', 0)" />
                </div>
                <div id="value_float_container" class="hidden">
                    <x-form.input type="number" name="value_float" id="value_float" :value="old('value_float', '0.00')" step="0.01" />
                </div>
                <div id="value_boolean_container" class="hidden">
                    <div class="flex items-center gap-4 p-4 border rounded-md">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="value_boolean" name="value_boolean"
                                {{ old('value_boolean') ? 'checked' : '' }}>
                        </div>
                        <label class="form-check-label font-medium" for="value_boolean">{{ __('settings::settings.create.value_boolean_label') }}</label>
                    </div>
                </div>
                <div id="value_file_container" class="hidden">
                    <input type="file" class="form-control form-control-sm" name="value_file" id="value_file">
                </div>
                <div id="value_image_container" class="hidden">
                    <input type="file" class="form-control form-control-sm" name="value_image" id="value_image"
                        accept="image/*">
                </div>
                <div id="value_json_container" class="hidden">
                    <div id="json-editor" class="border rounded-md"></div>
                    <x-form.input type="hidden" name="value_json" id="value_json" :value="old('value_json', '{}')" />
                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-light" id="format-json"><i
                                class="ph-brackets-curly"></i>{{ __('settings::settings.create.json_format') }}</button>
                        <button type="button" class="btn btn-sm btn-light" id="validate-json"><i
                                class="ph-check"></i>{{ __('settings::settings.create.json_validate') }}</button>
                        <span id="json-validation-result" class="ms-1 text-sm"></span>
                    </div>
                </div>
                <div id="value_select_container" class="hidden">
                    <x-alert type="info" icon="ph-info" class="mb-0">
                        <span class="text-sm">{{ __('settings::settings.create.select_hint') }}</span>
                    </x-alert>
                </div>
                <div id="value_multi-select_container" class="hidden">
                    <x-alert type="info" icon="ph-info" class="mb-0">
                        <span class="text-sm">{{ __('settings::settings.create.multiselect_hint') }}</span>
                    </x-alert>
                </div>
                <div id="value_array_container" class="hidden">
                    <div id="array_values_list">
                        <div class="input-group input-group-sm mb-2 array-value-row">
                            <x-form.input name="value_array[]" placeholder="{{ __('settings::settings.create.array_value_placeholder') }}" />
                            <button type="button" class="btn btn-outline-danger btn-icon" onclick="removeArrayValue(this)"><i
                                    class="ph-trash"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light" onclick="addArrayValue()">
                        <i class="ph-plus"></i>{{ __('settings::settings.create.add_value') }}
                    </button>
                </div>
            </div>
        </div>

        <div id="options_container" class="hidden">
            <x-form-section title="{{ __('settings::settings.create.section_options') }}" icon="ph-list-bullets">
                <x-slot name="badge">
                    <span class="text-muted text-xs ms-auto">{{ __('settings::settings.create.options_badge') }}</span>
                </x-slot>
                <div class="grid grid-cols-12 gap-0 mb-2 px-1">
                    <div class="col-span-5"><span class="fd-overline">{{ __('settings::settings.create.options_col_key') }}</span></div>
                    <div class="col-span-5"><span class="fd-overline">{{ __('settings::settings.create.options_col_label') }}</span></div>
                </div>
                <div id="options_list">
                    <div class="grid grid-cols-12 gap-2 mb-2 items-center option-row">
                        <div class="col-span-5"><x-form.input name="option_keys[]" placeholder="{{ __('settings::settings.create.option_key_placeholder') }}" /></div>
                        <div class="col-span-5"><x-form.input name="option_values[]" placeholder="{{ __('settings::settings.create.option_value_placeholder') }}" /></div>
                        <div class="col-span-12 md:col-span-2">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-option"
                                onclick="removeOption(this)"><i class="ph-trash"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-light mt-1" onclick="addOption()">
                    <i class="ph-plus"></i>{{ __('settings::settings.create.add_option') }}
                </button>
            </x-form-section>
        </div>

        {{-- Footer actions --}}
        <div class="fd-form-actions">
            <a href="{{ route('admin.settings.manage') }}" class="btn btn-light">
                <i class="ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary px-6">
                <i class="ph-floppy-disk"></i>{{ __('settings::settings.create.submit') }}
            </button>
        </div>

    </form>
@endsection



@push('scripts')
    <script src="{{ asset('assets/vendor/ace/ace.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#existing_group').select2({
                width: '100%',
                placeholder: '{{ __('settings::settings.create.group_select_placeholder') }}',
                allowClear: true
            });
            $('#existing_group').on('change', function() {
                var val = $(this).val();
                if (val === '__new__') {
                    $('#group').removeClass('hidden').focus();
                } else {
                    $('#group').addClass('hidden').val(val);
                }
            });
            var initialGroup = $('#existing_group').val();
            if (initialGroup && initialGroup !== '__new__') $('#group').val(initialGroup);

            $('#type').select2({
                width: '100%',
                placeholder: '{{ __('settings::settings.create.type_select_placeholder') }}',
                allowClear: false
            });
            $('#type').on('change', function() {
                showValueField();
            });
            showValueField();
        });

        function showValueField() {
            var type = document.getElementById('type').value;
            document.querySelectorAll('[id^="value_"][id$="_container"]').forEach(function(el) {
                el.classList.add('hidden');
            });
            document.getElementById('options_container').classList.add('hidden');
            document.getElementById('value_container').classList.toggle('hidden', !type);
            if (!type) return;
            var target = document.getElementById('value_' + type + '_container');
            if (target) target.classList.remove('hidden');
            if (type === 'select' || type === 'multi-select') document.getElementById('options_container').classList.remove(
                'hidden');
            if (type === 'json' && !window.jsonEditorInitialized) initJsonEditor();
        }

        function initJsonEditor() {
            var editor = ace.edit('json-editor');
            editor.setTheme('ace/theme/xcode');
            editor.session.setMode('ace/mode/json');
            editor.setOptions({
                fontSize: '13px',
                showPrintMargin: false,
                // Sizes the editor from its content instead of a fixed inline height.
                minLines: 18,
                maxLines: 18
            });
            try {
                editor.setValue(JSON.stringify(JSON.parse(document.getElementById('value_json').value || '{}'), null, 2), -
                    1);
            } catch (e) {
                editor.setValue('{}', -1);
            }
            editor.getSession().on('change', function() {
                try {
                    document.getElementById('value_json').value = JSON.stringify(JSON.parse(editor.getValue()));
                    document.getElementById('json-validation-result').innerHTML =
                        '<span class="text-success"><i class="ph-check me-1"></i>{{ __('settings::settings.create.json_valid') }}</span>';
                } catch (e) {
                    document.getElementById('json-validation-result').innerHTML = '<span class="text-danger">' + e
                        .message + '</span>';
                }
            });
            document.getElementById('format-json').addEventListener('click', function() {
                try {
                    editor.setValue(JSON.stringify(JSON.parse(editor.getValue()), null, 2), -1);
                } catch (e) {
                    window.showConfirm?.({
                        icon: 'error',
                        title: '{{ __('settings::settings.create.invalid_json_title') }}',
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
                        '<span class="text-success"><i class="ph-check me-1"></i>{{ __('settings::settings.create.json_valid_json') }}</span>';
                } catch (e) {
                    document.getElementById('json-validation-result').innerHTML = '<span class="text-danger">' + e
                        .message + '</span>';
                }
            });
            window.jsonEditorInitialized = true;
        }

        function addOption() {
            var row = '<div class="grid grid-cols-12 gap-2 mb-2 items-center option-row">' +
                '<div class="col-span-5"><input type="text" class="form-control form-control-sm" name="option_keys[]" placeholder="{{ __('settings::settings.create.option_key_placeholder') }}"></div>' +
                '<div class="col-span-5"><input type="text" class="form-control form-control-sm" name="option_values[]" placeholder="{{ __('settings::settings.create.option_value_placeholder') }}"></div>' +
                '<div class="col-span-12 md:col-span-2"><button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-option" onclick="removeOption(this)"><i class="ph-trash"></i></button></div>' +
                '</div>';
            document.getElementById('options_list').insertAdjacentHTML('beforeend', row);
        }

        function removeOption(btn) {
            if (document.querySelectorAll('.option-row').length > 1) btn.closest('.option-row').remove();
        }

        function addArrayValue() {
            var row =
                '<div class="input-group input-group-sm mb-2 array-value-row"><input type="text" class="form-control" name="value_array[]" placeholder="{{ __('settings::settings.create.array_value_placeholder') }}"><button type="button" class="btn btn-outline-danger btn-icon" onclick="removeArrayValue(this)"><i class="ph-trash"></i></button></div>';
            document.getElementById('array_values_list').insertAdjacentHTML('beforeend', row);
        }

        function removeArrayValue(btn) {
            btn.closest('.array-value-row').remove();
        }
    </script>
@endpush
