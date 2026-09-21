@extends('settings::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.settings.manage') }}" class="breadcrumb-item">Manage Settings</a>
    <span class="breadcrumb-item active">Edit Setting</span>
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

        <x-page-header title="Edit Setting" icon="ph-pencil-simple-line" :back-url="route('admin.settings.manage')" back-label="Back">
            <x-slot name="actions">
                <code
                    class="bg-primary bg-opacity-10 text-body rounded px-2 py-1 fs-xs fw-semibold">{{ $setting->key }}</code>
                <span class="badge bg-{{ $tc }}-subtle text-{{ $tc }} border border-{{ $tc }}-subtle text-uppercase fs-xs">{{ $setting->type }}</span>
            </x-slot>
        </x-page-header>

        {{-- Section 1: Identity --}}
        <x-form-section title="Identity" icon="ph-identification-card">
            <div class="row g-3">
                <div class="col-md-6">
                    {!! Form::label(null, 'Key', ['class' => 'form-label fw-semibold fs-sm']) !!}
                    {!! Form::text(null, $setting->key, [
                        'class' => 'form-control form-control-sm bg-body-tertiary',
                        'readonly',
                        'disabled',
                    ]) !!}
                    <div class="form-text">The key cannot be changed</div>
                </div>
                <div class="col-md-6">
                    {!! Form::label('existing_group', 'Group', ['class' => 'form-label fw-semibold fs-sm required']) !!}
                    {!! Form::select(
                        'existing_group',
                        collect($groups)->mapWithKeys(fn($g) => [$g => $g])->put('__new__', '+ Add new group…')->prepend('', '')->toArray(),
                        null,
                        [
                            'id' => 'existing_group',
                            'class' => 'form-control form-control-sm select' . ($errors->has('group') ? ' is-invalid' : ''),
                            'data-placeholder' => 'Select or type a group…',
                        ],
                    ) !!}
                    {!! Form::text('group', old('group', $setting->group), [
                        'id' => 'group',
                        'class' => 'form-control form-control-sm mt-2 d-none' . ($errors->has('group') ? ' is-invalid' : ''),
                        'placeholder' => 'New group name',
                    ]) !!}
                    @error('group')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    {!! Form::label('type', 'Type', ['class' => 'form-label fw-semibold fs-sm required']) !!}
                    {!! Form::select(
                        'type',
                        collect($types)->mapWithKeys(fn($t) => [$t => ucfirst($t)])->prepend('', '')->toArray(),
                        $setting->type,
                        [
                            'id' => 'type',
                            'class' => 'form-control form-control-sm select' . ($errors->has('type') ? ' is-invalid' : ''),
                            'data-placeholder' => 'Select type…',
                        ],
                    ) !!}
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    {!! Form::label('description', 'Description', ['class' => 'form-label fw-semibold fs-sm']) !!}
                    {!! Form::text('description', old('description', $setting->description), [
                        'class' => 'form-control form-control-sm' . ($errors->has('description') ? ' is-invalid' : ''),
                        'placeholder' => 'Short explanation of this setting',
                    ]) !!}
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </x-form-section>

        {{-- Section 2: Flags --}}
        <x-form-section title="Flags" icon="ph-toggle-right">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded border">
                        <div>
                            <div class="fw-semibold fs-sm">Visible in Settings Page</div>
                            <div class="text-muted fs-xs">Show this field to editors</div>
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
                            <div class="fw-semibold fs-sm">Required</div>
                            <div class="text-muted fs-xs">Must have a value</div>
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
                <span class="fw-semibold text-uppercase fs-xs">Current Value</span>
            </div>
            <div class="card-body">
                <div id="value_text_container" class="{{ $setting->type === 'text' ? '' : 'd-none' }}">
                    {!! Form::text('value_text', old('value_text', $setting->type === 'text' ? $setting->value : ''), [
                        'class' => 'form-control form-control-sm',
                    ]) !!}
                </div>
                <div id="value_textarea_container" class="{{ $setting->type === 'textarea' ? '' : 'd-none' }}">
                    {!! Form::textarea(
                        'value_textarea',
                        old('value_textarea', $setting->type === 'textarea' ? $setting->value : ''),
                        ['class' => 'form-control form-control-sm', 'rows' => 4],
                    ) !!}
                </div>
                <div id="value_integer_container" class="{{ $setting->type === 'integer' ? '' : 'd-none' }}">
                    {!! Form::number('value_integer', old('value_integer', $setting->type === 'integer' ? $setting->value : ''), [
                        'class' => 'form-control form-control-sm',
                    ]) !!}
                </div>
                <div id="value_float_container" class="{{ $setting->type === 'float' ? '' : 'd-none' }}">
                    {!! Form::number('value_float', old('value_float', $setting->type === 'float' ? $setting->value : ''), [
                        'class' => 'form-control form-control-sm',
                        'step' => '0.01',
                    ]) !!}
                </div>
                <div id="value_boolean_container" class="{{ $setting->type === 'boolean' ? '' : 'd-none' }}">
                    <div class="d-flex align-items-center gap-3 p-3 border rounded">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="value_boolean" name="value_boolean"
                                {{ $setting->type === 'boolean' && $setting->value ? 'checked' : '' }}>
                        </div>
                        <label class="form-check-label fw-medium" for="value_boolean">Enabled</label>
                    </div>
                </div>
                <div id="value_file_container" class="{{ $setting->type === 'file' ? '' : 'd-none' }}">
                    @if ($setting->type === 'file' && $setting->value)
                        <a href="{{ $setting->value }}" target="_blank"
                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 mb-2">
                            <i class="ph-file-arrow-down"></i> View Current File
                        </a>
                    @endif
                    <input type="file" class="form-control form-control-sm" name="value_file">
                    <div class="form-text">Leave empty to keep current file</div>
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
                    <div class="form-text">Leave empty to keep current image</div>
                </div>
                <div id="value_json_container" class="{{ $setting->type === 'json' ? '' : 'd-none' }}">
                    <div id="json-editor" class="border rounded" style="height:320px;"></div>
                    <input type="hidden" name="value_json" id="value_json"
                        value="{{ old('value_json', $setting->type === 'json' ? (is_string($setting->value) ? $setting->value : json_encode($setting->value)) : '{}') }}">
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-light border" id="format-json"><i
                                class="ph-brackets-curly me-1"></i>Format</button>
                        <button type="button" class="btn btn-sm btn-light border" id="validate-json"><i
                                class="ph-check me-1"></i>Validate</button>
                        <span id="json-validation-result" class="ms-1 fs-sm"></span>
                    </div>
                </div>
                <div id="value_select_container" class="{{ $setting->type === 'select' ? '' : 'd-none' }}">
                    @if ($setting->type === 'select' && is_array($setting->options) && count($setting->options))
                        {!! Form::select('value_select', ['' => ''] + $setting->options, $setting->value, [
                            'id' => 'value_select',
                            'class' => 'form-control form-control-sm select',
                            'data-placeholder' => 'Select a value…',
                        ]) !!}
                    @else
                        <p class="text-muted mb-0 fs-sm"><i class="ph-info me-1"></i>No options defined yet. Add options
                            below.</p>
                    @endif
                </div>
                <div id="value_multi-select_container" class="{{ $setting->type === 'multi-select' ? '' : 'd-none' }}">
                    @if ($setting->type === 'multi-select' && is_array($setting->options) && count($setting->options))
                        {!! Form::select('value_multi-select[]', $setting->options, is_array($setting->value) ? $setting->value : [], [
                            'id' => 'value_multi_select',
                            'class' => 'form-control form-control-sm select',
                            'multiple',
                            'data-placeholder' => 'Select options…',
                        ]) !!}
                    @else
                        <p class="text-muted mb-0 fs-sm"><i class="ph-info me-1"></i>No options defined yet. Add options
                            below.</p>
                    @endif
                </div>
                <div id="value_array_container" class="{{ $setting->type === 'array' ? '' : 'd-none' }}">
                    <div id="array_values_list">
                        @if ($setting->type === 'array' && is_array($setting->value) && count($setting->value))
                            @foreach ($setting->value as $val)
                                <div class="input-group input-group-sm mb-2 array-value-row">
                                    {!! Form::text('value_array[]', $val, ['class' => 'form-control form-control-sm']) !!}
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="removeArrayValue(this)"><i class="ph-trash"></i></button>
                                </div>
                            @endforeach
                        @else
                            <div class="input-group input-group-sm mb-2 array-value-row">
                                {!! Form::text('value_array[]', null, ['class' => 'form-control form-control-sm', 'placeholder' => 'Value']) !!}
                                <button type="button" class="btn btn-outline-danger" onclick="removeArrayValue(this)"><i
                                        class="ph-trash"></i></button>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addArrayValue()">
                        <i class="ph-plus me-1"></i>Add value
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
                    <span class="fw-semibold text-uppercase fs-xs">Options</span>
                </div>
                <span class="text-muted fs-xs">Define key → label pairs</span>
            </div>
            <div class="card-body">
                <div class="row g-0 mb-2 px-1">
                    <div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Key</span></div>
                    <div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Label</span></div>
                </div>
                <div id="options_list">
                    @if (in_array($setting->type, ['select', 'multi-select']) && is_array($setting->options) && count($setting->options))
                        @foreach ($setting->options as $optKey => $optVal)
                            <div class="row g-2 mb-2 align-items-center option-row">
                                <div class="col-5">{!! Form::text('option_keys[]', $optKey, ['class' => 'form-control form-control-sm']) !!}</div>
                                <div class="col-5">{!! Form::text('option_values[]', $optVal, ['class' => 'form-control form-control-sm']) !!}</div>
                                <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="removeOption(this)"><i class="ph-trash"></i></button></div>
                            </div>
                        @endforeach
                    @else
                        <div class="row g-2 mb-2 align-items-center option-row">
                            <div class="col-5">{!! Form::text('option_keys[]', null, [
                                'class' => 'form-control form-control-sm',
                                'placeholder' => 'e.g. active',
                            ]) !!}</div>
                            <div class="col-5">{!! Form::text('option_values[]', null, [
                                'class' => 'form-control form-control-sm',
                                'placeholder' => 'e.g. Active',
                            ]) !!}</div>
                            <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="removeOption(this)"><i class="ph-trash"></i></button></div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addOption()">
                    <i class="ph-plus me-1"></i>Add Option
                </button>
            </div>
        </div>

        {{-- Footer actions --}}
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.settings.manage') }}" class="btn btn-outline-secondary">
                <i class="ph-x me-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="ph-floppy-disk me-1"></i>Update Setting
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
                placeholder: 'Select or type a group…',
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
                placeholder: 'Select type…',
                allowClear: false
            });
            $('#type').on('change', function() {
                showValueField();
            });

            @if ($setting->type === 'select')
                $('#value_select').select2({
                    width: '100%',
                    placeholder: 'Select a value…',
                    allowClear: true
                });
            @elseif ($setting->type === 'multi-select')
                $('#value_multi_select').select2({
                    width: '100%',
                    placeholder: 'Select options…',
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
                            '<span class="text-success"><i class="ph-check me-1"></i>Valid</span>';
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
                        title: 'Invalid JSON',
                        text: e.message,
                        confirmClass: 'btn btn-danger',
                        showCancelButton: false,
                        confirmText: 'OK'
                    });
                }
            });
            document.getElementById('validate-json').addEventListener('click', function() {
                try {
                    JSON.parse(editor.getValue());
                    document.getElementById('json-validation-result').innerHTML =
                        '<span class="text-success"><i class="ph-check me-1"></i>Valid JSON</span>';
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
                        title: 'Invalid JSON',
                        text: err.message,
                        confirmClass: 'btn btn-danger',
                        showCancelButton: false,
                        confirmText: 'OK'
                    });
                }
            });
            window.jsonEditorInitialized = true;
        }

        function addOption() {
            var row = '<div class="row g-2 mb-2 align-items-center option-row">' +
                '<div class="col-5"><input type="text" class="form-control form-control-sm" name="option_keys[]" placeholder="e.g. active"></div>' +
                '<div class="col-5"><input type="text" class="form-control form-control-sm" name="option_values[]" placeholder="e.g. Active"></div>' +
                '<div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOption(this)"><i class="ph-trash"></i></button></div></div>';
            document.getElementById('options_list').insertAdjacentHTML('beforeend', row);
        }

        function removeOption(btn) {
            if (document.querySelectorAll('.option-row').length > 1) btn.closest('.option-row').remove();
        }

        function addArrayValue() {
            var row =
                '<div class="input-group input-group-sm mb-2 array-value-row"><input type="text" class="form-control" name="value_array[]" placeholder="Value"><button type="button" class="btn btn-outline-danger" onclick="removeArrayValue(this)"><i class="ph-trash"></i></button></div>';
            document.getElementById('array_values_list').insertAdjacentHTML('beforeend', row);
        }

        function removeArrayValue(btn) {
            btn.closest('.array-value-row').remove();
        }
    </script>
@endpush
