@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">SMS Gateways</span>
@endsection

@section('content')
    <div class="card">

        {{-- Page header --}}
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-chat-text"></i>
                </div>
                <div>
                    <div class="fw-bold">SMS Gateways</div>
                    <div class="text-muted fs-xs">Configure SMS providers via HTTP API</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="test_mobile_no" class="form-control form-control-sm" placeholder="+880123456789"
                    style="width:180px;">
                <button type="button" id="sendSmsBtn" class="btn btn-sm btn-outline-primary">
                    <i class="ph-paper-plane-tilt me-1"></i>Send Test
                </button>
            </div>
        </div>

        <div class="card-body p-4">

            {{-- Quick-ref --}}
            <details class="mb-4">
                <summary class="d-flex align-items-center gap-2 p-3 rounded border bg-body-tertiary fw-semibold fs-sm"
                    class="cursor-pointer" style="list-style:none;">
                    <i class="ph-question text-primary"></i> Basic Auth header example (click to expand)
                </summary>
                <div class="border border-top-0 rounded-bottom p-3 fs-sm">
                    <p class="mb-1">To add Basic Authentication, add a header with:</p>
                    <ul class="mb-1 text-muted">
                        <li>Key: <code>Authorization</code></li>
                        <li>Value: <code>Basic &lt;base64(username:password)&gt;</code></li>
                    </ul>
                    <p class="mb-0">Generate base64 at <a href="https://www.base64encode.org/"
                            target="_blank">base64encode.org</a>.</p>
                </div>
            </details>

            <form action="{{ route('admin.settings.special.update_sms_gateways') }}" method="POST">
                @csrf

                {{-- Active gateway selector --}}
                <div class="card mb-4">
                    <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
                        <i class="ph-check-circle text-primary"></i>
                        <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">Active Gateway</span>
                    </div>
                    <div class="card-body">
                        {!! Form::label('sms_gateway', 'Selected SMS Gateway', ['class' => 'form-label fw-semibold fs-sm']) !!}
                        {!! Form::select(
                            'sms_gateway',
                            is_array($smsGateways->value)
                                ? array_combine(array_column($smsGateways->value, 'TYPE'), array_column($smsGateways->value, 'TYPE'))
                                : [],
                            $smsGateway->value ?? null,
                            [
                                'id' => 'sms_gateway',
                                'class' => 'form-control form-control-sm select',
                                'data-placeholder' => 'Select SMS Gateway...',
                                'placeholder' => 'Select SMS Gateway...',
                            ],
                        ) !!}
                        <div class="form-text">The gateway that will be used to send all system SMS messages</div>
                    </div>
                </div>

                {{-- Gateway cards --}}
                <div id="smsInputFieldsContainer">
                    @if (is_array($smsGateways->value) && count($smsGateways->value) > 0)
                        @foreach ($smsGateways->value as $index => $gateway)
                            <div class="card mb-3">
                                <div
                                    class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ph-chat-text text-primary"></i>
                                        <span class="fw-semibold fs-sm">{{ $gateway['TYPE'] }}</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-gw-btn">
                                        <i class="ph-trash me-1"></i>Remove
                                    </button>
                                </div>
                                <div class="card-body">

                                    {{-- Core fields --}}
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            {!! Form::label("sms_gateways[{$index}][TYPE]", 'Type', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                            {!! Form::text("sms_gateways[{$index}][TYPE]", $gateway['TYPE'], [
                                                'class' => 'form-control form-control-sm gw-type-input',
                                                'required',
                                            ]) !!}
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::label("sms_gateways[{$index}][VALUE][endpoint]", 'Endpoint', [
                                                'class' => 'form-label fw-semibold fs-sm',
                                            ]) !!}<span class="text-danger"> *</span>
                                            {!! Form::text("sms_gateways[{$index}][VALUE][endpoint]", $gateway['VALUE']['endpoint'] ?? '', [
                                                'class' => 'form-control form-control-sm',
                                                'required',
                                                'placeholder' => 'https://api.provider.com/sms/send',
                                            ]) !!}
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::label("sms_gateways[{$index}][VALUE][method]", 'Method', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                            {!! Form::select(
                                                "sms_gateways[{$index}][VALUE][method]",
                                                ['GET' => 'GET', 'POST' => 'POST'],
                                                $gateway['VALUE']['method'] ?? 'POST',
                                                ['class' => 'form-control form-control-sm', 'required'],
                                            ) !!}
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::label("sms_gateways[{$index}][VALUE][mobile_prefix]", 'Mobile Prefix', [
                                                'class' => 'form-label fw-semibold fs-sm',
                                            ]) !!}
                                            {!! Form::text("sms_gateways[{$index}][VALUE][mobile_prefix]", $gateway['VALUE']['mobile_prefix'] ?? '', [
                                                'class' => 'form-control form-control-sm',
                                                'placeholder' => 'e.g. +60',
                                            ]) !!}
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::label("sms_gateways[{$index}][VALUE][mobile_key]", 'Mobile Key', [
                                                'class' => 'form-label fw-semibold fs-sm',
                                            ]) !!}<span class="text-danger"> *</span>
                                            {!! Form::text("sms_gateways[{$index}][VALUE][mobile_key]", $gateway['VALUE']['mobile_key'] ?? '', [
                                                'class' => 'form-control form-control-sm',
                                                'required',
                                                'placeholder' => 'mobile',
                                            ]) !!}
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::label("sms_gateways[{$index}][VALUE][message_key]", 'Message Key', [
                                                'class' => 'form-label fw-semibold fs-sm',
                                            ]) !!}<span class="text-danger"> *</span>
                                            {!! Form::text("sms_gateways[{$index}][VALUE][message_key]", $gateway['VALUE']['message_key'] ?? '', [
                                                'class' => 'form-control form-control-sm',
                                                'required',
                                                'placeholder' => 'message',
                                            ]) !!}
                                        </div>
                                    </div>

                                    {{-- Headers --}}
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-1 mb-2 text-muted fw-bold text-uppercase fs-xs"
                                            style="letter-spacing:.04em;">
                                            <i class="ph-list-bullets text-primary"></i> Headers
                                        </div>
                                        <div class="row g-0 mb-1 px-1">
                                            <div class="col-5"><span
                                                    class="text-muted fw-bold text-uppercase fs-xs">Key</span></div>
                                            <div class="col-5"><span
                                                    class="text-muted fw-bold text-uppercase fs-xs">Value</span></div>
                                        </div>
                                        <div class="headers-container">
                                            @if (isset($gateway['VALUE']['headers']) && is_array($gateway['VALUE']['headers']))
                                                @foreach ($gateway['VALUE']['headers'] as $hKey => $hVal)
                                                    <div class="row g-1 mb-1 align-items-center header-row">
                                                        <div class="col-5">{!! Form::text("sms_gateways[{$index}][VALUE][headers][keys][]", $hKey, [
                                                            'class' => 'form-control form-control-sm',
                                                            'placeholder' => 'Authorization',
                                                        ]) !!}</div>
                                                        <div class="col-5">{!! Form::text(
                                                            "sms_gateways[{$index}][VALUE][headers][values][]",
                                                            '',
                                                            ['class' => 'form-control form-control-sm', 'placeholder' => !empty($hVal) ? '•••• (unchanged)' : 'Basic …'],
                                                        ) !!}</div>
                                                        <div class="col-md-2"><button type="button"
                                                                class="btn btn-sm btn-outline-danger remove-kv-btn"><i
                                                                    class="ph-trash"></i></button></div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary add-header-btn mt-1"
                                            data-index="{{ $index }}">
                                            <i class="ph-plus me-1"></i>Add Header
                                        </button>
                                    </div>

                                    {{-- Params --}}
                                    <div>
                                        <div class="d-flex align-items-center gap-1 mb-2 text-muted fw-bold text-uppercase fs-xs"
                                            style="letter-spacing:.04em;">
                                            <i class="ph-sliders text-primary"></i> Additional Parameters
                                        </div>
                                        <div class="row g-0 mb-1 px-1">
                                            <div class="col-5"><span
                                                    class="text-muted fw-bold text-uppercase fs-xs">Key</span></div>
                                            <div class="col-5"><span
                                                    class="text-muted fw-bold text-uppercase fs-xs">Value</span></div>
                                        </div>
                                        <div class="params-container">
                                            @if (isset($gateway['VALUE']['params']) && is_array($gateway['VALUE']['params']))
                                                @foreach ($gateway['VALUE']['params'] as $pKey => $pVal)
                                                    <div class="row g-1 mb-1 align-items-center param-row">
                                                        <div class="col-5">{!! Form::text("sms_gateways[{$index}][VALUE][params][keys][]", $pKey, [
                                                            'class' => 'form-control form-control-sm',
                                                            'placeholder' => 'api_key',
                                                        ]) !!}</div>
                                                        <div class="col-5">{!! Form::text("sms_gateways[{$index}][VALUE][params][values][]", '', [
                                                            'class' => 'form-control form-control-sm',
                                                            'placeholder' => !empty($pVal) ? '•••• (unchanged)' : 'value',
                                                        ]) !!}</div>
                                                        <div class="col-md-2"><button type="button"
                                                                class="btn btn-sm btn-outline-danger remove-kv-btn"><i
                                                                    class="ph-trash"></i></button></div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary add-param-btn mt-1"
                                            data-index="{{ $index }}">
                                            <i class="ph-plus me-1"></i>Add Parameter
                                        </button>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="d-flex align-items-center justify-content-between mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addGatewayBtn">
                        <i class="ph-plus me-1"></i>Add Gateway
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="ph-floppy-disk me-1"></i>Save Gateways
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Remove kv row (delegated)
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-kv-btn');
            if (btn) {
                btn.closest('.row').remove();
                return;
            }

            var rem = e.target.closest('.remove-gw-btn');
            if (rem) {
                window.showConfirm({
                    title: 'Remove this gateway?',
                    icon: 'warning',
                    confirmText: 'Remove',
                    confirmClass: 'btn btn-danger',
                    onConfirm: function() {
                        rem.closest('.card').remove();
                    }
                });
                return;
            }

            var hBtn = e.target.closest('.add-header-btn');
            if (hBtn) {
                let idx = hBtn.dataset.index;
                let row = `<div class="row g-1 mb-1 align-items-center header-row">
            <div class="col-5"><input type="text" class="form-control form-control-sm" name="sms_gateways[${idx}][VALUE][headers][keys][]" placeholder="Authorization"></div>
            <div class="col-5"><input type="text" class="form-control form-control-sm" name="sms_gateways[${idx}][VALUE][headers][values][]" placeholder="Basic …"></div>
            <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-kv-btn"><i class="ph-trash"></i></button></div>
        </div>`;
                hBtn.previousElementSibling.insertAdjacentHTML('beforeend', row);
                return;
            }

            var pBtn = e.target.closest('.add-param-btn');
            if (pBtn) {
                let idx = pBtn.dataset.index;
                let row = `<div class="row g-1 mb-1 align-items-center param-row">
            <div class="col-5"><input type="text" class="form-control form-control-sm" name="sms_gateways[${idx}][VALUE][params][keys][]" placeholder="api_key"></div>
            <div class="col-5"><input type="text" class="form-control form-control-sm" name="sms_gateways[${idx}][VALUE][params][values][]" placeholder="value"></div>
            <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-kv-btn"><i class="ph-trash"></i></button></div>
        </div>`;
                pBtn.previousElementSibling.insertAdjacentHTML('beforeend', row);
            }
        });

        // Add gateway
        var gwIndex = {{ is_array($smsGateways->value) ? count($smsGateways->value) : 0 }};
        document.getElementById('addGatewayBtn').addEventListener('click', function() {
            var idx = gwIndex++;
            var html = `
    <div class="card mb-3">
        <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="ph-chat-text text-primary"></i>
                <span class="fw-semibold fs-sm">New Gateway</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-gw-btn"><i class="ph-trash me-1"></i>Remove</button>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Type <span class="text-danger">*</span></label>
                    <input type="text" name="sms_gateways[${idx}][TYPE]" class="form-control form-control-sm gw-type-input" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Endpoint <span class="text-danger">*</span></label>
                    <input type="text" name="sms_gateways[${idx}][VALUE][endpoint]" class="form-control form-control-sm" required placeholder="https://api.provider.com/sms/send">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Method <span class="text-danger">*</span></label>
                    <select name="sms_gateways[${idx}][VALUE][method]" class="form-control form-control-sm" required>
                        <option value="GET">GET</option><option value="POST" selected>POST</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Mobile Prefix</label>
                    <input type="text" name="sms_gateways[${idx}][VALUE][mobile_prefix]" class="form-control form-control-sm" placeholder="+60">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Mobile Key <span class="text-danger">*</span></label>
                    <input type="text" name="sms_gateways[${idx}][VALUE][mobile_key]" class="form-control form-control-sm" required placeholder="mobile">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Message Key <span class="text-danger">*</span></label>
                    <input type="text" name="sms_gateways[${idx}][VALUE][message_key]" class="form-control form-control-sm" required placeholder="message">
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex align-items-center gap-1 mb-2 text-muted fw-bold text-uppercase fs-xs" style="letter-spacing:.04em;"><i class="ph-list-bullets text-primary"></i> Headers</div>
                <div class="row g-0 mb-1 px-1"><div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Key</span></div><div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Value</span></div></div>
                <div class="headers-container"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary add-header-btn mt-1" data-index="${idx}"><i class="ph-plus me-1"></i>Add Header</button>
            </div>
            <div>
                <div class="d-flex align-items-center gap-1 mb-2 text-muted fw-bold text-uppercase fs-xs" style="letter-spacing:.04em;"><i class="ph-sliders text-primary"></i> Additional Parameters</div>
                <div class="row g-0 mb-1 px-1"><div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Key</span></div><div class="col-5"><span class="text-muted fw-bold text-uppercase fs-xs">Value</span></div></div>
                <div class="params-container"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary add-param-btn mt-1" data-index="${idx}"><i class="ph-plus me-1"></i>Add Parameter</button>
            </div>
        </div>
    </div>`;
            document.getElementById('smsInputFieldsContainer').insertAdjacentHTML('beforeend', html);
        });

        // Send test SMS
        document.getElementById('sendSmsBtn').addEventListener('click', function() {
            var mobile = document.getElementById('test_mobile_no').value.trim();
            if (!mobile) {
                window.showConfirm({
                    icon: 'error',
                    title: 'Required',
                    text: 'Please enter a mobile number.',
                    confirmClass: 'btn btn-danger',
                    showCancelButton: false,
                    confirmText: 'OK'
                });
                return;
            }
            var btn = this,
                orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ph-circle-notch ph-spin me-1"></i>Sending…';
            $.post('{{ route('admin.settings.special.send_test_sms') }}', {
                    mobile_no: mobile,
                    _token: '{{ csrf_token() }}'
                })
                .done(function(r) {
                    window.toast('success', 'Sent', r.message);
                })
                .fail(function(r) {
                    window.showConfirm({
                        icon: 'error',
                        title: 'Failed',
                        text: r.responseJSON ? r.responseJSON.message : 'An error occurred.',
                        confirmClass: 'btn btn-danger',
                        showCancelButton: false,
                        confirmText: 'OK'
                    });
                })
                .always(function() {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                });
        });
    </script>
@endpush
