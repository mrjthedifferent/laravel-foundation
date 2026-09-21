@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Email Mailers</span>
@endsection

@php
    $transportOptions = [
        'smtp' => 'SMTP (username & password)',
        'microsoft_oauth' => 'Microsoft 365 / Outlook (SMTP OAuth2)',
        'microsoft_graph' => 'Microsoft 365 / Outlook (Graph API)',
        'sendmail' => 'Sendmail',
        'log' => 'Log (write to log file)',
        'array' => 'Array (discard)',
    ];
@endphp

@section('content')
<div class="card">

    {{-- Page header --}}
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                <i class="ph-envelope"></i>
            </div>
            <div>
                <div class="fw-bold">Email Mailers</div>
                <div class="text-muted fs-xs">Configure SMTP mailer providers</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="email" id="test_email_address" class="form-control form-control-sm"
                   placeholder="test@example.com" style="width:200px;">
            <button type="button" id="sendEmailBtn" class="btn btn-sm btn-outline-primary">
                <i class="ph-paper-plane-tilt me-1"></i>Send Test
            </button>
        </div>
    </div>

    <div class="card-body p-4">

        {{-- Provider quick-ref --}}
        <details class="mb-4">
            <summary class="d-flex align-items-center gap-2 p-3 rounded border bg-body-tertiary fw-semibold fs-sm cursor-pointer" style="list-style:none;">
                <i class="ph-question text-primary"></i> Common provider settings (click to expand)
            </summary>
            <div class="border border-top-0 rounded-bottom p-3 fs-sm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="fw-semibold mb-1">Gmail</div>
                        <ul class="mb-0 ps-3 text-muted">
                            <li>Transport: <code>smtp</code></li>
                            <li>Host: <code>smtp.gmail.com</code> · Port: <code>587</code> · Encryption: <code>tls</code></li>
                            <li>Username: your Gmail address</li>
                            <li>Password: <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> (not your Gmail password)</li>
                        </ul>
                    </div>
                    <div class="col-md-8">
                        <div class="fw-semibold mb-1">Microsoft 365 / Outlook (OAuth2)</div>
                        <p class="text-muted mb-2">
                            Microsoft is retiring SMTP basic authentication for Exchange Online — disabled by default
                            for existing tenants at the end of December 2026. Use the
                            <code>microsoft_oauth</code> transport, which authenticates with a token instead of a
                            mailbox password. A password is never entered.
                        </p>
                        <ul class="mb-2 ps-3 text-muted">
                            <li>Register an app in <strong>Microsoft Entra ID → App registrations</strong> (single tenant), then copy the <strong>Tenant ID</strong>, <strong>Client ID</strong> and a new <strong>Client Secret</strong>.</li>
                            <li><strong>API permissions</strong> → <em>APIs my organization uses</em> → <strong>Office 365 Exchange Online</strong> → <strong>Application permissions</strong> → <code>SMTP.SendAsApp</code>, then <strong>Grant admin consent</strong>.</li>
                            <li>Run once per sending mailbox in Exchange Online PowerShell — the Object ID must come from <strong>Enterprise applications</strong>, not App registrations:</li>
                        </ul>
<pre class="bg-body-tertiary border rounded p-2 mb-2 fs-xs mb-0"><code>Connect-ExchangeOnline -Organization &lt;TENANT_ID&gt;
New-ServicePrincipal -AppId &lt;CLIENT_ID&gt; -ObjectId &lt;ENTERPRISE_APP_OBJECT_ID&gt;
Add-MailboxPermission -Identity &lt;MAILBOX&gt; -User &lt;SERVICE_PRINCIPAL_ID&gt; -AccessRights FullAccess</code></pre>
                        <div class="text-muted">
                            Keep <strong>From Address</strong> equal to the mailbox, otherwise Exchange replies
                            <code>550 5.7.60 SendAsDenied</code> unless the service principal also has a
                            <code>SendAs</code> grant.
                            <a href="https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth" target="_blank">Microsoft docs</a>
                        </div>
                        <div class="alert alert-info d-flex gap-2 mt-3 mb-0 py-2 px-3 fs-sm">
                            <i class="ph-info flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>No PowerShell access, or Security Defaults blocking SMTP?</strong>
                                Use the <code>Microsoft 365 / Outlook (Graph API)</code> transport instead. It takes the
                                same Tenant ID, Client ID, Client Secret and Mailbox, but needs only the Microsoft Graph
                                <code>Mail.Send</code> <em>application</em> permission (admin consented) — no
                                <code>New-ServicePrincipal</code> / <code>Add-MailboxPermission</code> steps and no SMTP
                                basic auth.
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
                <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
                    <i class="ph-check-circle text-primary"></i>
                    <span class="fw-bold text-uppercase fs-xs" style="letter-spacing:.05em;">Active Mailer</span>
                </div>
                <div class="card-body">
                    {!! Form::label('email_mailer', 'Selected Email Mailer', ['class' => 'form-label fw-semibold fs-sm']) !!}
                    {!! Form::select('email_mailer', is_array($emailMailers->value) ? array_combine(array_column($emailMailers->value, 'TYPE'), array_column($emailMailers->value, 'TYPE')) : [], isset($emailMailer) ? $emailMailer->value : null, ['id' => 'email_mailer', 'class' => 'form-control form-control-sm select', 'data-placeholder' => 'Select Email Mailer...', 'placeholder' => 'Select Email Mailer...']) !!}
                    <div class="form-text">The mailer that will be used to send all system emails</div>
                </div>
            </div>

            {{-- Mailer cards --}}
            <div id="emailInputFieldsContainer">
                @if(is_array($emailMailers->value) && count($emailMailers->value) > 0)
                    @foreach($emailMailers->value as $index => $mailer)
                    <div class="card mb-3 mailer-card">
                        <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ph-envelope-simple text-primary"></i>
                                <span class="fw-semibold fs-sm">{{ $mailer['TYPE'] }}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-mailer-btn">
                                <i class="ph-trash me-1"></i>Remove
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    {!! Form::label("email_mailers[{$index}][TYPE]", 'Type', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::text("email_mailers[{$index}][TYPE]", $mailer['TYPE'], ['class' => 'form-control form-control-sm mailer-type-input', 'required', 'placeholder' => 'e.g. Gmail']) !!}
                                    <div class="form-text">Unique name for this mailer</div>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::label("email_mailers[{$index}][VALUE][transport]", 'Transport', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::select("email_mailers[{$index}][VALUE][transport]", $transportOptions, $mailer['VALUE']['transport'] ?? 'smtp', ['class' => 'form-select form-select-sm transport-select', 'required']) !!}
                                </div>

                                {{-- SMTP (password) credentials --}}
                                <div class="col-md-4 transport-fields-smtp">
                                    {!! Form::label("email_mailers[{$index}][VALUE][host]", 'Host', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::text("email_mailers[{$index}][VALUE][host]", $mailer['VALUE']['host'] ?? '', ['class' => 'form-control form-control-sm', 'placeholder' => 'smtp.gmail.com']) !!}
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    {!! Form::label("email_mailers[{$index}][VALUE][port]", 'Port', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::number("email_mailers[{$index}][VALUE][port]", $mailer['VALUE']['port'] ?? 587, ['class' => 'form-control form-control-sm', 'placeholder' => '587']) !!}
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    {!! Form::label("email_mailers[{$index}][VALUE][encryption]", 'Encryption', ['class' => 'form-label fw-semibold fs-sm']) !!}
                                    {!! Form::text("email_mailers[{$index}][VALUE][encryption]", $mailer['VALUE']['encryption'] ?? 'tls', ['class' => 'form-control form-control-sm', 'placeholder' => 'tls']) !!}
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    {!! Form::label("email_mailers[{$index}][VALUE][username]", 'Username', ['class' => 'form-label fw-semibold fs-sm']) !!}
                                    {!! Form::text("email_mailers[{$index}][VALUE][username]", $mailer['VALUE']['username'] ?? '', ['class' => 'form-control form-control-sm', 'placeholder' => 'you@example.com']) !!}
                                </div>
                                <div class="col-md-4 transport-fields-smtp">
                                    {!! Form::label("email_mailers[{$index}][VALUE][password]", 'Password', ['class' => 'form-label fw-semibold fs-sm']) !!}
                                    <div class="input-group input-group-sm">
                                        {!! Form::password("email_mailers[{$index}][VALUE][password]", ['class' => 'form-control form-control-sm', 'placeholder' => !empty($mailer['VALUE']['password']) ? '•••••••• (unchanged)' : 'Enter password']) !!}
                                        <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                                    </div>
                                    <div class="form-text">Leave blank to keep the current password</div>
                                </div>

                                {{-- Microsoft 365 OAuth2 credentials --}}
                                <div class="col-md-4 transport-fields-oauth">
                                    {!! Form::label("email_mailers[{$index}][VALUE][tenant_id]", 'Tenant ID', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::text("email_mailers[{$index}][VALUE][tenant_id]", $mailer['VALUE']['tenant_id'] ?? '', ['class' => 'form-control form-control-sm', 'placeholder' => '00000000-0000-0000-0000-000000000000']) !!}
                                    <div class="form-text">Entra ID → Overview → Tenant ID</div>
                                </div>
                                <div class="col-md-4 transport-fields-oauth">
                                    {!! Form::label("email_mailers[{$index}][VALUE][client_id]", 'Client ID', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::text("email_mailers[{$index}][VALUE][client_id]", $mailer['VALUE']['client_id'] ?? '', ['class' => 'form-control form-control-sm', 'placeholder' => 'Application (client) ID']) !!}
                                </div>
                                <div class="col-md-4 transport-fields-oauth">
                                    {!! Form::label("email_mailers[{$index}][VALUE][client_secret]", 'Client Secret', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    <div class="input-group input-group-sm">
                                        {!! Form::password("email_mailers[{$index}][VALUE][client_secret]", ['class' => 'form-control form-control-sm', 'placeholder' => !empty($mailer['VALUE']['client_secret']) ? '•••••••• (unchanged)' : 'Client secret value']) !!}
                                        <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                                    </div>
                                    <div class="form-text">The secret <em>value</em>, not the secret ID · leave blank to keep the current secret</div>
                                </div>
                                <div class="col-md-4 transport-fields-oauth">
                                    {!! Form::label("email_mailers[{$index}][VALUE][mailbox]", 'Mailbox', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::email("email_mailers[{$index}][VALUE][mailbox]", $mailer['VALUE']['mailbox'] ?? '', ['class' => 'form-control form-control-sm', 'placeholder' => 'noreply@yourcompany.com']) !!}
                                    <div class="form-text">Mailbox granted to the service principal</div>
                                </div>

                                {{-- Shared --}}
                                <div class="col-md-4">
                                    {!! Form::label("email_mailers[{$index}][VALUE][from][address]", 'From Address', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::email("email_mailers[{$index}][VALUE][from][address]", $mailer['VALUE']['from']['address'] ?? '', ['class' => 'form-control form-control-sm', 'required', 'placeholder' => 'noreply@yourcompany.com']) !!}
                                </div>
                                <div class="col-md-4">
                                    {!! Form::label("email_mailers[{$index}][VALUE][from][name]", 'From Name', ['class' => 'form-label fw-semibold fs-sm']) !!}<span class="text-danger"> *</span>
                                    {!! Form::text("email_mailers[{$index}][VALUE][from][name]", $mailer['VALUE']['from']['name'] ?? '', ['class' => 'form-control form-control-sm', 'required', 'placeholder' => 'Your Company']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <div class="d-flex align-items-center justify-content-between mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="addMailerBtn">
                    <i class="ph-plus me-1"></i>Add Mailer
                </button>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ph-floppy-disk me-1"></i>Save Mailers
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
        title: 'Remove this mailer?',
        icon: 'warning',
        confirmText: 'Remove',
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
        <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="ph-envelope-simple text-primary"></i>
                <span class="fw-semibold fs-sm">New Mailer</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-mailer-btn"><i class="ph-trash me-1"></i>Remove</button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Type <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][TYPE]" class="form-control form-control-sm mailer-type-input" required placeholder="e.g. Outlook365">
                    <div class="form-text">Unique name for this mailer</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">Transport <span class="text-danger">*</span></label>
                    <select name="email_mailers[${idx}][VALUE][transport]" class="form-select form-select-sm transport-select" required>
                        @foreach ($transportOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">Host <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][host]" class="form-control form-control-sm" placeholder="smtp.gmail.com">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">Port <span class="text-danger">*</span></label>
                    <input type="number" name="email_mailers[${idx}][VALUE][port]" class="form-control form-control-sm" value="587">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">Encryption</label>
                    <input type="text" name="email_mailers[${idx}][VALUE][encryption]" class="form-control form-control-sm" value="tls">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">Username</label>
                    <input type="text" name="email_mailers[${idx}][VALUE][username]" class="form-control form-control-sm" placeholder="you@example.com">
                </div>
                <div class="col-md-4 transport-fields-smtp">
                    <label class="form-label fw-semibold fs-sm">Password</label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="email_mailers[${idx}][VALUE][password]" class="form-control form-control-sm">
                        <button type="button" class="btn btn-outline-secondary toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                    </div>
                </div>

                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">Tenant ID <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][tenant_id]" class="form-control form-control-sm" placeholder="00000000-0000-0000-0000-000000000000">
                    <div class="form-text">Entra ID &rarr; Overview &rarr; Tenant ID</div>
                </div>
                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">Client ID <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][client_id]" class="form-control form-control-sm" placeholder="Application (client) ID">
                </div>
                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">Client Secret <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="email_mailers[${idx}][VALUE][client_secret]" class="form-control form-control-sm">
                        <button type="button" class="btn btn-outline-secondary toggle-pw" tabindex="-1"><i class="ph-eye"></i></button>
                    </div>
                    <div class="form-text">The secret <em>value</em>, not the secret ID</div>
                </div>
                <div class="col-md-4 transport-fields-oauth d-none">
                    <label class="form-label fw-semibold fs-sm">Mailbox <span class="text-danger">*</span></label>
                    <input type="email" name="email_mailers[${idx}][VALUE][mailbox]" class="form-control form-control-sm" placeholder="noreply@yourcompany.com">
                    <div class="form-text">Mailbox granted to the service principal</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">From Address <span class="text-danger">*</span></label>
                    <input type="email" name="email_mailers[${idx}][VALUE][from][address]" class="form-control form-control-sm" required placeholder="noreply@yourcompany.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-sm">From Name <span class="text-danger">*</span></label>
                    <input type="text" name="email_mailers[${idx}][VALUE][from][name]" class="form-control form-control-sm" required placeholder="Your Company">
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
        window.showConfirm({ icon: 'error', title: 'Invalid email', text: 'Please enter a valid email address.', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' });
        return;
    }
    var btn = this, orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ph-circle-notch ph-spin me-1"></i>Sending…';
    $.post('{{ route("admin.settings.special.send_test_email") }}', { email: email, _token: '{{ csrf_token() }}' })
        .done(function (r) {
            window.toast('success', 'Sent', r.message);
        })
        .fail(function (r) {
            window.showConfirm({ icon: 'error', title: 'Failed', text: r.responseJSON ? r.responseJSON.message : 'An error occurred.', confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' });
        })
        .always(function () { btn.disabled = false; btn.innerHTML = orig; });
});
</script>
@endpush
