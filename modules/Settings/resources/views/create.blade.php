@extends('settings::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.settings.manage') }}" class="breadcrumb-item">Manage Settings</a>
    <span class="breadcrumb-item active">Create Setting</span>
@endsection

@section('content')
    <form action="{{ route('admin.settings.store_new') }}" method="POST" enctype="multipart/form-data" id="create-form">
        @csrf

        <x-page-header title="Create New Setting" subtitle="Add a new configuration key to the system" icon="ph-plus"
            :back-url="route('admin.settings.manage')" back-label="Back" />

        <x-form-section title="Identity" icon="ph-identification-card">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold fs-sm required">Key</label>
                    <input type="text" class="form-control form-control-sm @error('key') is-invalid @enderror"
                        name="key" id="key" value="{{ old('key') }}" placeholder="e.g. site_title" required>
                    <div class="form-text">Unique identifier — use snake_case</div>
                    @error('key')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
                    {!! Form::text('group', old('group'), [
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
                        old('type'),
                        [
                            'id' => 'type',
                            'class' => 'form-control form-control-sm select' . ($errors->has('type') ? ' is-invalid' : ''),
                            'data-placeholder' => 'Select type…',
                            'required',
                        ],
                    ) !!}
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    {!! Form::label('description', 'Description', ['class' => 'form-label fw-semibold fs-sm']) !!}
                    {!! Form::text('description', old('description'), [
                        'id' => 'description',
                        'class' => 'form-control form-control-sm' . ($errors->has('description') ? ' is-invalid' : ''),
                        'placeholder' => 'Short explanation of this setting',
                    ]) !!}
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Flags" icon="ph-toggle-right">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded border">
                        <div>
                            <div class="fw-semibold fs-sm">Visible in Settings Page</div>
                            <div class="text-muted fs-xs">Show this field to editors</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" checked>
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
                            <input class="form-check-input" type="checkbox" id="is_required" name="is_required" checked>
                        </div>
                    </div>
                </div>
            </div>
        </x-form-section>

        <div class="card mb-3 d-none" id="value_container">
            <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
                <i class="ph-pencil-line text-primary"></i>
                <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">Initial Value</span>
            </div>
            <div class="card-body">
                <div id="value_text_container" class="d-none">
                    {!! Form::text('value_text', old('value_text'), [
                        'id' => 'value_text',
                        'class' => 'form-control form-control-sm',
                        'placeholder' => 'Text value',
                    ]) !!}
                </div>
                <div id="value_textarea_container" class="d-none">
                    {!! Form::textarea('value_textarea', old('value_textarea'), [
                        'id' => 'value_textarea',
                        'class' => 'form-control form-control-sm',
                        'rows' => 4,
                        'placeholder' => 'Textarea value',
                    ]) !!}
                </div>
                <div id="value_encrypted_container" class="d-none">
                    {!! Form::password('value_encrypted', [
                        'id' => 'value_encrypted',
                        'class' => 'form-control form-control-sm',
                        'placeholder' => 'Secret value — stored encrypted',
                    ]) !!}
                </div>
                <div id="value_integer_container" class="d-none">
                    {!! Form::number('value_integer', old('value_integer', 0), [
                        'id' => 'value_integer',
                        'class' => 'form-control form-control-sm',
                    ]) !!}
                </div>
                <div id="value_float_container" class="d-none">
                    {!! Form::number('value_float', old('value_float', '0.00'), [
                        'id' => 'value_float',
                        'class' => 'form-control form-control-sm',
                        'step' => '0.01',
                    ]) !!}
                </div>
                <div id="value_boolean_container" class="d-none">
                    <div class="d-flex align-items-center gap-3 p-3 border rounded">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="value_boolean" name="value_boolean"
                                {{ old('value_boolean') ? 'checked' : '' }}>
                        </div>
                        <label class="form-check-label fw-medium" for="value_boolean">Enabled by default</label>
                    </div>
                </div>
                <div id="value_file_container" class="d-none">
                    <input type="file" class="form-control form-control-sm" name="value_file" id="value_file">
                </div>
                <div id="value_image_container" class="d-none">
                    <input type="file" class="form-control form-control-sm" name="value_image" id="value_image"
                        accept="image/*">
                </div>
                <div id="value_json_container" class="d-none">
                    <div id="json-editor" class="border rounded" style="height:320px;"></div>
                    {!! Form::hidden('value_json', old('value_json', '{}'), ['id' => 'value_json']) !!}
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-light border" id="format-json"><i
                                class="ph-brackets-curly me-1"></i>Format</button>
                        <button type="button" class="btn btn-sm btn-light border" id="validate-json"><i
                                class="ph-check me-1"></i>Validate</button>
                        <span id="json-validation-result" class="ms-1 fs-sm"></span>
                    </div>
                </div>
                <div id="value_select_container" class="d-none">
                    <x-alert type="info" icon="ph-info" class="mb-0">
                        <span class="fs-sm">Define the options below, then set the default selected value after
                            saving.</span>
                    </x-alert>
                </div>
                <div id="value_multi-select_container" class="d-none">
                    <x-alert type="info" icon="ph-info" class="mb-0">
                        <span class="fs-sm">Define the options below, then set the default selected values after
                            saving.</span>
                    </x-alert>
                </div>
                <div id="value_array_container" class="d-none">
                    <div id="array_values_list">
                        <div class="input-group input-group-sm mb-2 array-value-row">
                            {!! Form::text('value_array[]', null, ['class' => 'form-control form-control-sm', 'placeholder' => 'Value']) !!}
                            <button type="button" class="btn btn-outline-danger" onclick="removeArrayValue(this)"><i
                                    class="ph-trash"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addArrayValue()">
                        <i class="ph-plus me-1"></i>Add value
                    </button>
                </div>
            </div>
        </div>

        <div id="options_container" class="d-none">
            <x-form-section title="Options" icon="ph-list-bullets">
                <x-slot name="badge">
                    <span class="text-muted fs-xs ms-auto">Define key → label pairs</span>
                </x-slot>
                <div class="row g-0 mb-2 px-1">
                    <div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Key</span></div>
                    <div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Label</span></div>
                </div>
                <div id="options_list">
                    <div class="row g-2 mb-2 align-items-center option-row">
                        <div class="col-5">{!! Form::text('option_keys[]', null, [
                            'class' => 'form-control form-control-sm',
                            'placeholder' => 'e.g. active',
                        ]) !!}</div>
                        <div class="col-5">{!! Form::text('option_values[]', null, [
                            'class' => 'form-control form-control-sm',
                            'placeholder' => 'e.g. Active',
                        ]) !!}</div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-option"
                                onclick="removeOption(this)"><i class="ph-trash"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addOption()">
                    <i class="ph-plus me-1"></i>Add Option
                </button>
            </x-form-section>
        </div>

        {{-- Footer actions --}}
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.settings.manage') }}" class="btn btn-outline-secondary">
                <i class="ph-x me-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="ph-floppy-disk me-1"></i>Create Setting
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
            var initialGroup = $('#existing_group').val();
            if (initialGroup && initialGroup !== '__new__') $('#group').val(initialGroup);

            $('#type').select2({
                width: '100%',
                placeholder: 'Select type…',
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
            var editor = ace.edit('json-editor');
            editor.setTheme('ace/theme/xcode');
            editor.session.setMode('ace/mode/json');
            editor.setOptions({
                fontSize: '13px',
                showPrintMargin: false
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
                        '<span class="text-success"><i class="ph-check me-1"></i>Valid</span>';
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
            window.jsonEditorInitialized = true;
        }

        function addOption() {
            var row = '<div class="row g-2 mb-2 align-items-center option-row">' +
                '<div class="col-5"><input type="text" class="form-control form-control-sm" name="option_keys[]" placeholder="e.g. active"></div>' +
                '<div class="col-5"><input type="text" class="form-control form-control-sm" name="option_values[]" placeholder="e.g. Active"></div>' +
                '<div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-option" onclick="removeOption(this)"><i class="ph-trash"></i></button></div>' +
                '</div>';
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
