@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_email_mailers.breadcrumb') }}</span>
@endsection

@php
    $transportOptions = [
        'smtp' => __('settings::settings.special_email_mailers.transport_smtp'),
        'microsoft_oauth' => __('settings::settings.special_email_mailers.transport_microsoft_oauth'),
        'microsoft_graph' => __('settings::settings.special_email_mailers.transport_microsoft_graph'),
        'sendmail' => __('settings::settings.special_email_mailers.transport_sendmail'),
        'log' => __('settings::settings.special_email_mailers.transport_log'),
        'array' => __('settings::settings.special_email_mailers.transport_array'),
    ];
@endphp

@section('content')
<div class="card">

    {{-- Page header --}}
    <div class="card-header">
        <span class="fd-icon-tile"><i class="ph-envelope"></i></span>
        <div>
            <div class="card-title">{{ __('settings::settings.special_email_mailers.title') }}</div>
            <div class="text-muted fs-xs">{{ __('settings::settings.special_email_mailers.subtitle') }}</div>
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <input type="email" id="test_email_address" class="form-control form-control-sm w-sm"
                   aria-label="{{ __('settings::settings.special_email_mailers.send_test') }}" placeholder="test@example.com">
            <button type="button" id="sendEmailBtn" class="btn btn-sm btn-light">
                <i class="ph-paper-plane-tilt"></i>{{ __('settings::settings.special_email_mailers.send_test') }}
            </button>
        </div>
    </div>

    <div class="card-body p-4">

        {{-- Provider quick-ref --}}
        <details class="mb-4">
            <summary class="d-flex align-items-center gap-2 p-3 rounded border bg-body-tertiary fw-semibold fs-sm cursor-pointer">
                <i class="ph-question"></i> {{ __('settings::settings.special_email_mailers.quickref_summary') }}
            </summary>
            <div class="border border-top-0 rounded-bottom p-3 fs-sm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="fw-semibold mb-1">{{ __('settings::settings.special_email_mailers.gmail_heading') }}</div>
                        <ul class="mb-0 ps-3 text-muted">
                            {!! __('settings::settings.special_email_mailers.gmail_block') !!}
                        </ul>
                    </div>
                    <div class="col-md-8">
                        <div class="fw-semibold mb-1">{{ __('settings::settings.special_email_mailers.ms365_heading') }}</div>
                        <p class="text-muted mb-2">
                            {!! __('settings::settings.special_email_mailers.ms365_intro') !!}
                        </p>
                        <ul class="mb-2 ps-3 text-muted">
                            {!! __('settings::settings.special_email_mailers.ms365_steps') !!}
                        </ul>
<pre class="bg-body-tertiary border rounded p-2 mb-2 fs-xs mb-0"><code>Connect-ExchangeOnline -Organization &lt;TENANT_ID&gt;
New-ServicePrincipal -AppId &lt;CLIENT_ID&gt; -ObjectId &lt;ENTERPRISE_APP_OBJECT_ID&gt;
Add-MailboxPermission -Identity &lt;MAILBOX&gt; -User &lt;SERVICE_PRINCIPAL_ID&gt; -AccessRights FullAccess</code></pre>
                        <div class="text-muted">
                            {!! __('settings::settings.special_email_mailers.ms365_from_note') !!}
                        </div>
                        <div class="alert alert-info d-flex gap-2 mt-3 mb-0 py-2 px-3 fs-sm">
                            <i class="ph-info flex-shrink-0 mt-1"></i>
                            <div>
                                {!! __('settings::settings.special_email_mailers.ms365_graph_alert') !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </details>

        <form action="{{ route('admin.settings.special.update_email_mailers') }}" method="POST" id="email-mailers-form">
            @csrf

            {{-- Active mailer selector --}}
            <div class="card mb-4">
                <div class="card-header">
                    <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-check-circle"></i></span>
                    <span class="fd-overline">{{ __('settings::settings.special_email_mailers.active_mailer_header') }}</span>
                </div>
                <div class="card-body">
                    <x-form.select
                        class="select"
                        name="email_mailer"
                        id="email_mailer"
                        label="{{ __('settings::settings.special_email_mailers.selected_mailer_label') }}"
                        :options="is_array($emailMailers->value) ? array_combine(array_column($emailMailers->value, 'TYPE'), array_column($emailMailers->value, 'TYPE')) : []"
                        :selected="isset($emailMailer) ? $emailMailer->value : null"
                        data-placeholder="{{ __('settings::settings.special_email_mailers.select_mailer_placeholder') }}"
                        placeholder="{{ __('settings::settings.special_email_mailers.select_mailer_placeholder') }}"
                    />
                    <div class="form-text">{{ __('settings::settings.special_email_mailers.active_mailer_help') }}</div>
                </div>
            </div>

            {{-- Mailer cards --}}
            <div id="emailInputFieldsContainer">
                @if(is_array($emailMailers->value) && count($emailMailers->value) > 0)
                    @foreach($emailMailers->value as $index => $mailer)
                    <div class="card mb-3 mailer-card">
                        <div class="card-header">
                            <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-envelope-simple"></i></span>
                            <span class="card-title">{{ $mailer['TYPE'] }}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-mailer-btn ms-auto">
                                <i class="ph-trash"></i>{{ __('settings::settings.common.remove') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <x-form.input name="email_mailers[{{ $index }}][TYPE]" label="{{ __('settings::settings.special_email_mailers.field_type') }}" required :value="$mailer['TYPE']" class="mailer-type-input" placeholder="e.g. Gmail" />
                                    <div class="form-text">{{ __('settings::settings.special_email_mailers.type_help') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <x-form.select name="email_mailers[{{ $index }}][VALUE][transport]" label="{{ __('settings::settings.special_email_mailers.field_transport') }}" required class="transport-select" :options="$transportOptions" :selected="$mailer['VALUE']['transport'] ?? 'smtp'" />
                                </div>

                                {{-- SMTP (password) credentials --}}
                                <div class="col-md-4 transport-fields-smtp">
                                    <x-form.input name="email_mailers[{{ $index }}][VALUE][host]" label="{{ __('settings::settings.special_email_mailers.field_host') }}" required :value="$mailer['VALUE']['host'] ?? ''" placeholder="smtp.gmail.com" />
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    <x-form.input type="number" name="email_mailers[{{ $index }}][VALUE][port]" label="{{ __('settings::settings.special_email_mailers.field_port') }}" required :value="$mailer['VALUE']['port'] ?? 587" placeholder="587" />
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    <x-form.input name="email_mailers[{{ $index }}][VALUE][encryption]" label="{{ __('settings::settings.special_email_mailers.field_encryption') }}" :value="$mailer['VALUE']['encryption'] ?? 'tls'" placeholder="tls" />
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    <x-form.input name="email_mailers[{{ $index }}][VALUE][username]" label="{{ __('settings::settings.special_email_mailers.field_username') }}" :value="$mailer['VALUE']['username'] ?? ''" placeholder="you@example.com" />
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    <x-form.label for="email_mailers_{{ $index }}_password">{{ __('settings::settings.special_email_mailers.field_password') }}</x-form.label>
                                    <div class="input-group input-group-sm">
                                        <x-form.input type="password" name="email_mailers[{{ $index }}][VALUE][password]" id="email_mailers_{{ $index }}_password" :placeholder="! empty($mailer['VALUE']['password']) ? '•••••••• (unchanged)' : __('settings::settings.special_email_mailers.enter_password')" />
                                        <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost pw-toggle text-muted position-absolute top-50 end-0 translate-middle-y toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                                    </div>
                                    <div class="form-text">{{ __('settings::settings.special_email_mailers.password_help') }}</div>
                                </div>

                                {{-- Microsoft 365 OAuth2 credentials --}}
                                <div class="col-md-4 transport-fields-oauth">
                                    <x-form.input name="email_mailers[{{ $index }}][VALUE][tenant_id]" label="{{ __('settings::settings.special_email_mailers.field_tenant_id') }}" required :value="$mailer['VALUE']['tenant_id'] ?? ''" placeholder="00000000-0000-0000-0000-000000000000" />
                                    <div class="form-text">{{ __('settings::settings.special_email_mailers.tenant_id_help') }}</div>
                                </div>
                                <div class="col-md-4 transport-fields-oauth">
                                    <x-form.input name="email_mailers[{{ $index }}][VALUE][client_id]" label="{{ __('settings::settings.special_email_mailers.field_client_id') }}" required :value="$mailer['VALUE']['client_id'] ?? ''" placeholder="{{ __('settings::settings.special_email_mailers.client_id_placeholder') }}" />
                                </div>
                                <div class="col-md-4 transport-fields-oauth">
                                    <x-form.label for="email_mailers_{{ $index }}_client_secret" required>{{ __('settings::settings.special_email_mailers.field_client_secret') }}</x-form.label>
                                    <div class="input-group input-group-sm">
                                        <x-form.input type="password" name="email_mailers[{{ $index }}][VALUE][client_secret]" id="email_mailers_{{ $index }}_client_secret" :placeholder="! empty($mailer['VALUE']['client_secret']) ? '•••••••• (unchanged)' : __('settings::settings.special_email_mailers.enter_client_secret')" />
                                        <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost pw-toggle text-muted position-absolute top-50 end-0 translate-middle-y toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                                    </div>
                                    <div class="form-text">{!! __('settings::settings.special_email_mailers.client_secret_help') !!}</div>
                                </div>
                                <div class="col-md-4 transport-fields-oauth">
                                    <x-form.input type="email" name="email_mailers[{{ $index }}][VALUE][mailbox]" label="{{ __('settings::settings.special_email_mailers.field_mailbox') }}" required :value="$mailer['VALUE']['mailbox'] ?? ''" placeholder="noreply@yourcompany.com" />
                                    <div class="form-text">{{ __('settings::settings.special_email_mailers.mailbox_help') }}</div>
                                </div>

                                {{-- Shared --}}
                                <div class="col-md-4">
                                    <x-form.input type="email" name="email_mailers[{{ $index }}][VALUE][from][address]" label="{{ __('settings::settings.special_email_mailers.field_from_address') }}" required :value="$mailer['VALUE']['from']['address'] ?? ''" placeholder="noreply@yourcompany.com" />
                                </div>
                                <div class="col-md-4">
                                    <x-form.input name="email_mailers[{{ $index }}][VALUE][from][name]" label="{{ __('settings::settings.special_email_mailers.field_from_name') }}" required :value="$mailer['VALUE']['from']['name'] ?? ''" placeholder="{{ __('settings::settings.special_email_mailers.from_name_placeholder') }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <div class="d-flex align-items-center justify-content-between mt-2">
                <button type="button" class="btn btn-sm btn-light" id="addMailerBtn">
                    <i class="ph-plus"></i>{{ __('settings::settings.special_email_mailers.add_mailer') }}
                </button>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ph-floppy-disk"></i>{{ __('settings::settings.special_email_mailers.save_mailers') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Password toggle
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.toggle-pw');
    if (!btn) return;
    var input = btn.previousElementSibling;
    var icon  = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'ph-eye' : 'ph-eye-slash';
});

// Show only the credential fields the selected transport needs. Hidden inputs
// keep their names, so a transport switch does not discard the other set until
// the form is saved; the FormRequest decides which fields are required.
function applyTransportVisibility(select) {
    var card = select.closest('.mailer-card');
    if (!card) return;
    var transport = select.value;
    card.querySelectorAll('.transport-fields-smtp').forEach(function (el) {
        el.classList.toggle('d-none', transport !== 'smtp');
    });
    var usesEntra = transport === 'microsoft_oauth' || transport === 'microsoft_graph';
    card.querySelectorAll('.transport-fields-oauth').forEach(function (el) {
        el.classList.toggle('d-none', !usesEntra);
    });
}

document.querySelectorAll('.transport-select').forEach(applyTransportVisibility);

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('transport-select')) {
        applyTransportVisibility(e.target);
    }
});

// Remove mailer
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.remove-mailer-btn');
    if (!btn) return;
    window.showConfirm({
        title: '{{ __('settings::settings.special_email_mailers.remove_mailer_confirm') }}',
        icon: 'warning',
        confirmText: '{{ __('settings::settings.common.remove') }}',
        confirmClass: 'btn btn-danger',
        onConfirm: function() { btn.closest('.card').remove(); }
    });
});

// Add mailer
var mailerIndex = {{ is_array($emailMailers->value) ? count($emailMailers->value) : 0 }};
document.getElementById('addMailerBtn').addEventListener('click', function () {
    var idx = mailerIndex++;
    var html = `
    <div class="card mb-3 mailer-card">
        <div class="card-header">
            <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-envelope-simple"></i></span>
            <span class="card-title">{{ __('settings::settings.special_email_mailers.new_mailer') }}</span>
            <button type="button" class="btn btn-sm btn-outline-danger remove-mailer-btn ms-auto"><i class="ph-trash"></i>{{ __('settings::settings.common.remove') }}</button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_type') }} <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][TYPE]" class="form-control form-control-sm mailer-type-input" required placeholder="e.g. Outlook365">
                    <div class="form-text">{{ __('settings::settings.special_email_mailers.type_help') }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_transport') }} <span class="text-danger">*</span></label>
                    <select name="email_mailers[${idx}][VALUE][transport]" class="form-select form-select-sm transport-select" required>
                        @foreach ($transportOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_host') }} <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][host]" class="form-control form-control-sm" placeholder="smtp.gmail.com">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_port') }} <span class="text-danger">*</span></label>
                    <input type="number" name="email_mailers[${idx}][VALUE][port]" class="form-control form-control-sm" value="587">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_encryption') }}</label>
                    <input type="text" name="email_mailers[${idx}][VALUE][encryption]" class="form-control form-control-sm" value="tls">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_username') }}</label>
                    <input type="text" name="email_mailers[${idx}][VALUE][username]" class="form-control form-control-sm" placeholder="you@example.com">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_password') }}</label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="email_mailers[${idx}][VALUE][password]" class="form-control form-control-sm">
                        <button type="button" class="btn btn-light toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                    </div>
                </div>

                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_tenant_id') }} <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][tenant_id]" class="form-control form-control-sm" placeholder="00000000-0000-0000-0000-000000000000">
                    <div class="form-text">{{ __('settings::settings.special_email_mailers.tenant_id_help') }}</div>
                </div>
                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_client_id') }} <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][client_id]" class="form-control form-control-sm" placeholder="{{ __('settings::settings.special_email_mailers.client_id_placeholder') }}">
                </div>
                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_client_secret') }} <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="email_mailers[${idx}][VALUE][client_secret]" class="form-control form-control-sm">
                        <button type="button" class="btn btn-light toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                    </div>
                    <div class="form-text">{!! __('settings::settings.special_email_mailers.client_secret_short_help') !!}</div>
                </div>
                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_mailbox') }} <span class="text-danger">*</span></label>
                    <input type="email" name="email_mailers[${idx}][VALUE][mailbox]" class="form-control form-control-sm" placeholder="noreply@yourcompany.com">
                    <div class="form-text">{{ __('settings::settings.special_email_mailers.mailbox_help') }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_from_address') }} <span class="text-danger">*</span></label>
                    <input type="email" name="email_mailers[${idx}][VALUE][from][address]" class="form-control form-control-sm" required placeholder="noreply@yourcompany.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">{{ __('settings::settings.special_email_mailers.field_from_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][from][name]" class="form-control form-control-sm" required placeholder="{{ __('settings::settings.special_email_mailers.from_name_placeholder') }}">
                </div>
            </div>
        </div>
    </div>`;
    document.getElementById('emailInputFieldsContainer').insertAdjacentHTML('beforeend', html);
});

// Send test email
document.getElementById('sendEmailBtn').addEventListener('click', function () {
    var email = document.getElementById('test_email_address').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        window.showConfirm({ icon: 'error', title: '{{ __('settings::settings.special_email_mailers.test_email_invalid_title') }}', text: '{{ __('settings::settings.special_email_mailers.test_email_invalid_text') }}', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: '{{ __('settings::settings.common.ok') }}' });
        return;
    }
    var btn = this, orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ph-circle-notch ph-spin"></i>{{ __('settings::settings.common.sending') }}';
    $.post('{{ route("admin.settings.special.send_test_email") }}', { email: email, _token: '{{ csrf_token() }}' })
        .done(function (r) {
            window.toast('success', '{{ __('settings::settings.special_email_mailers.test_email_sent_title') }}', r.message);
        })
        .fail(function (r) {
            window.showConfirm({ icon: 'error', title: '{{ __('settings::settings.special_email_mailers.test_email_failed_title') }}', text: r.responseJSON ? r.responseJSON.message : '{{ __('settings::settings.common.error_occurred') }}', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: '{{ __('settings::settings.common.ok') }}' });
        })
        .always(function () { btn.disabled = false; btn.innerHTML = orig; });
});
</script>
@endpush
