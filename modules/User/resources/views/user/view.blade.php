@extends('user::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User List</a></span>
    <span class="breadcrumb-item active">{{ $user->name }}</span>
@endsection

@section('content')
    {{-- Header Card --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $user->image }}" class="rounded-circle border flex-shrink-0"
                            style="height:70px;width:70px;object-fit:cover;" alt="{{ $user->name }}">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $user->name }}</h5>
                            <p class="mb-1 text-muted fs-sm">
                                @if ($user->email)
                                    <i class="ph-envelope me-1"></i> {{ $user->email }}
                                    @if ($user->email_verified_at)
                                        <i class="ph-check-circle text-success ms-1" title="Email Verified"></i>
                                    @endif
                                @endif
                                @if ($user->phone)
                                    @if ($user->email)
                                        <span class="mx-2">|</span>
                                    @endif
                                    <i class="ph-phone me-1"></i> {{ $user->phone }}
                                    @if ($user->phone_verified_at)
                                        <i class="ph-check-circle text-success ms-1" title="Phone Verified"></i>
                                    @endif
                                @endif
                            </p>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($user->roles as $role)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $role->name }}</span>
                                @endforeach
                                <x-status-badge :active="$user->is_active" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end mt-3 mt-md-0">
                    @can('Edit User')
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary me-2">
                            <i class="ph-pencil-simple me-1"></i> Edit User
                        </a>
                    @endcan
                    @can('User Password Reset')
                        <a href="{{ route('admin.user.password.reset', $user->id) }}"
                            class="btn btn-sm btn-outline-warning swal-confirm"
                            data-text="Are you sure you want to reset this user's password?">
                            <i class="ph-key me-1"></i> Reset Password
                        </a>
                    @endcan
                    @can('impersonate', $user)
                        <a href="{{ route('admin.users.impersonate', $user->id) }}"
                            class="btn btn-sm btn-outline-dark ms-2 swal-post"
                            data-text="Sign in as {{ $user->name }}? You can return to your account from the banner at the top.">
                            <i class="ph-user-switch me-1"></i> Impersonate
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="user-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab"
                        data-bs-target="#personal" type="button" role="tab" aria-selected="true">
                        <i class="ph-user me-1"></i> Personal Info
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab"
                        data-bs-target="#documents" type="button" role="tab" aria-selected="false">
                        <i class="ph-files me-1"></i> Documents
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1">{{ $user->documents->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="login-history-tab" data-bs-toggle="tab"
                        data-bs-target="#login-history" type="button" role="tab" aria-selected="false">
                        <i class="ph-clock-counter-clockwise me-1"></i> Login History
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="user-tab-content">

                {{-- Tab 1: Personal Information --}}
                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-nowrap mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted" width="140">ID</th>
                                        <td>{{ $user->id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">UUID</th>
                                        <td><small class="font-monospace">{{ $user->uuid ?? 'N/A' }}</small></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Full Name</th>
                                        <td>{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Email</th>
                                        <td>
                                            {{ $user->email ?? 'N/A' }}
                                            @if ($user->email && $user->email_verified_at)
                                                <i class="ph-check-circle text-success ms-1" title="Verified"></i>
                                            @elseif ($user->email)
                                                <i class="ph-x-circle text-warning ms-1" title="Not Verified"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Gender</th>
                                        <td>{{ ucfirst($user->gender?->value ?? 'N/A') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Roles</th>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-muted">No roles assigned</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Status</th>
                                        <td>
                                            <x-status-badge :active="$user->is_active" />
                                        </td>
                                    </tr>
                                    @if ($user->provider)
                                        <tr>
                                            <th class="text-muted">Social Login</th>
                                            <td><span class="badge bg-info-subtle text-info border border-info-subtle">{{ ucfirst($user->provider) }}</span></td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-nowrap mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted" width="160">Last Login</th>
                                        <td>
                                            @if ($user->latestLogin)
                                                {{ $user->latestLogin->logged_in_at->format('d M Y h:i A') }}
                                                <br>
                                                <small class="text-muted">{{ $user->latestLogin->logged_in_at->diffForHumans() }}</small>
                                            @else
                                                <span class="text-muted">Never logged in</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Created</th>
                                        <td>
                                            {{ $user->created_at->format('d M Y h:i A') }}
                                            <br>
                                            <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Updated</th>
                                        <td>
                                            {{ $user->updated_at->format('d M Y h:i A') }}
                                            <br>
                                            <small class="text-muted">{{ $user->updated_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Email Verified</th>
                                        <td>
                                            @if ($user->email_verified_at)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Verified</span>
                                                <br>
                                                <small class="text-muted">{{ $user->email_verified_at->format('d M Y') }}</small>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Not Verified</span>
                                                @can('Verify User Contact')
                                                    @if ($user->email)
                                                        <form method="POST" action="{{ route('admin.users.verify.email', $user) }}" class="d-inline ms-2">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success swal-confirm"
                                                                data-text="Manually mark this user's email as verified?">
                                                                <i class="ph-check-circle me-1"></i>Verify Email
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Phone</th>
                                        <td>{{ $user->phone ?? '—' }}</td>
                                    </tr>
                                    @if ($user->phone)
                                    <tr>
                                        <th class="text-muted">Phone Verified</th>
                                        <td>
                                            @if ($user->phone_verified_at)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Verified</span>
                                                <br>
                                                <small class="text-muted">{{ $user->phone_verified_at->format('d M Y') }}</small>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Not Verified</span>
                                                @can('Verify User Contact')
                                                    <form method="POST" action="{{ route('admin.users.verify.phone', $user) }}" class="d-inline ms-2">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success swal-confirm"
                                                            data-text="Manually mark this user's phone as verified?">
                                                            <i class="ph-check-circle me-1"></i>Verify Phone
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Documents --}}
                <div class="tab-pane fade" id="documents" role="tabpanel">
                    <div class="table-responsive mb-4">
                        <table class="table table-hover table-borderless table-nowrap align-middle">
                            <thead class="table-active">
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Number</th>
                                    <th>Files</th>
                                    <th>Expiry Date</th>
                                    <th>Uploaded At</th>
                                    @can('Edit User')
                                        <th class="text-end">Action</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($user->documents as $document)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $document->document_type->label() }}</td>
                                        <td>{{ $document->document_number ?? 'N/A' }}</td>
                                        <td>
                                            <a href="{{ \Mrj\Foundation\Services\FileManagerService::getFile($document->file_path) }}"
                                                target="_blank" class="btn btn-sm btn-outline-info me-1">
                                                <i class="ph-eye me-1"></i> Front
                                            </a>
                                            @if ($document->back_file_path)
                                                <a href="{{ \Mrj\Foundation\Services\FileManagerService::getFile($document->back_file_path) }}"
                                                    target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="ph-eye me-1"></i> Back
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $document->expiry_date ? $document->expiry_date->format('d M Y') : 'N/A' }}
                                            @if ($document->expiry_date && $document->expiry_date->isPast())
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">Expired</span>
                                            @endif
                                        </td>
                                        <td>{{ $document->created_at->format('d M Y') }}</td>
                                        @can('Edit User')
                                            <td class="text-end">
                                                <x-dropdown-menu>
                                                    <button type="button" class="dropdown-item text-danger swal-delete"
                                                        data-url="{{ route('admin.users.documents.destroy', [$user->id, $document->id]) }}"
                                                        data-text="Are you sure you want to delete this document?">
                                                        <i class="ph-trash me-2"></i> Delete
                                                    </button>
                                                </x-dropdown-menu>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="ph-folder-open d-block mb-2 opacity-25 fs-4"></i>
                                            No documents uploaded yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Upload Form --}}
                    @can('Edit User')
                        <div class="border-top pt-4">
                            <div class="fw-semibold fs-sm mb-3">
                                <i class="ph-upload me-1 text-primary"></i> Upload New Document
                            </div>
                            <form action="{{ route('admin.users.documents.store', $user->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <x-form.select name="document_type" label="Document Type" required :options="['' => 'Select Type'] + collect(\Modules\User\Enum\DocumentType::cases())->mapWithKeys(fn($t) => [$t->value => $t->label()])->toArray()" :selected="null" />
                                    </div>
                                    <div class="col-md-4">
                                        <x-form.input name="document_number" label="Document Number" placeholder="Enter document number" />
                                    </div>
                                    <div class="col-md-4">
                                        <x-form.input type="date" name="expiry_date" label="Expiry Date" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-form.file name="file" label="Front Side File" required accept="image/*,.pdf" />
                                        <div class="form-text">Accepted: images, PDF</div>
                                    </div>
                                    <div class="col-md-6">
                                        <x-form.label for="back_file">Back Side File</x-form.label>
                                        <span class="text-muted fw-normal">(Optional)</span>
                                        <x-form.file name="back_file" accept="image/*,.pdf" />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <x-primary-button type="submit">
                                        <i class="ph-upload me-1"></i> Upload Document
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    @endcan
                </div>

                {{-- Tab 3: Login History --}}
                <div class="tab-pane fade" id="login-history" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-nowrap align-middle">
                            <thead class="table-active">
                                <tr>
                                    <th>#</th>
                                    <th>Device</th>
                                    <th>Browser</th>
                                    <th>Platform</th>
                                    <th>IP Address</th>
                                    <th>Logged In</th>
                                    <th>Logged Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loginHistory as $history)
                                    <tr>
                                        <td>{{ $loginHistory->firstItem() + $loop->index }}</td>
                                        <td>
                                            @if ($history->device_type === 'mobile')
                                                <i class="ph-device-mobile text-muted me-1"></i>
                                            @elseif ($history->device_type === 'tablet')
                                                <i class="ph-device-tablet text-muted me-1"></i>
                                            @else
                                                <i class="ph-desktop text-muted me-1"></i>
                                            @endif
                                            {{ ucfirst($history->device_type ?? 'N/A') }}
                                        </td>
                                        <td>{{ $history->browser ?? 'N/A' }}</td>
                                        <td>{{ $history->platform ?? 'N/A' }}</td>
                                        <td><span class="font-monospace fs-xs">{{ $history->ip_address ?? 'N/A' }}</span></td>
                                        <td>
                                            {{ $history->logged_in_at->format('d M Y h:i A') }}
                                            <br>
                                            <small class="text-muted">{{ $history->logged_in_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if ($history->logged_out_at)
                                                {{ $history->logged_out_at->format('d M Y h:i A') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="ph-clock-counter-clockwise d-block mb-2 opacity-25 fs-4"></i>
                                            No login history recorded
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $loginHistory->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Manage Account (non-production only) --}}
    @if (!app()->environment('production'))
        @can('Delete User')
            <div class="card border-danger mt-3" id="manage-account">
                <div class="card-header bg-danger text-white d-flex align-items-center gap-2">
                    <i class="ph-warning-octagon"></i>
                    <span class="fw-semibold">Manage Account</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.account.manage', $user->id) }}">
                        @csrf
                        <div class="row align-items-start g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="action"
                                            id="actionReset" value="reset" checked
                                            onchange="updateManageWarning()">
                                        <label class="form-check-label" for="actionReset">
                                            <i class="ph-arrow-counter-clockwise text-warning me-1"></i> Reset Account
                                        </label>
                                        <div class="text-muted fs-xs ms-4">Clears profile data, keeps login credentials</div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action"
                                            id="actionDelete" value="delete"
                                            onchange="updateManageWarning()">
                                        <label class="form-check-label" for="actionDelete">
                                            <i class="ph-trash text-danger me-1"></i> Delete Account
                                        </label>
                                        <div class="text-muted fs-xs ms-4">Permanently removes the user and all data</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="manageResetWarning" class="alert alert-warning mb-0">
                                    <strong>Warning:</strong> This will clear all user data but retain login credentials.
                                </div>
                                <div id="manageDeleteWarning" class="alert alert-danger mb-0 d-none">
                                    <strong>Danger:</strong> This will permanently delete the user and all data. This action cannot be undone.
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" id="manageConfirmButton" class="btn btn-warning swal-confirm"
                                    data-text="This action cannot be undone. Are you sure you want to proceed?">
                                <i class="ph-arrow-counter-clockwise me-1"></i> Confirm Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash;
            if (hash) {
                const tabButton = document.querySelector('#user-tabs button[data-bs-target="' + hash + '"]');
                if (tabButton) bootstrap.Tab.getOrCreateInstance(tabButton).show();
            }
            document.querySelectorAll('#user-tabs button[data-bs-toggle="tab"]').forEach(function (button) {
                button.addEventListener('shown.bs.tab', function (e) {
                    const target = e.target.dataset.bsTarget;
                    history.replaceState(null, null, target);
                    if (target === '#login-history') appendFragmentToPagination();
                });
            });
            if (hash === '#login-history') appendFragmentToPagination();
        });

        function appendFragmentToPagination() {
            document.querySelectorAll('#login-history .pagination a[href]').forEach(function (link) {
                if (!link.href.includes('#')) link.href = link.href + '#login-history';
            });
        }

    </script>

    @if (!app()->environment('production'))
        <script>
            function updateManageWarning() {
                const isDelete = document.getElementById('actionDelete').checked;
                document.getElementById('manageResetWarning').classList.toggle('d-none', isDelete);
                document.getElementById('manageDeleteWarning').classList.toggle('d-none', !isDelete);
                const btn = document.getElementById('manageConfirmButton');
                if (isDelete) {
                    btn.innerHTML = '<i class="ph-trash me-1"></i> Confirm Delete';
                    btn.className = 'btn btn-danger';
                } else {
                    btn.innerHTML = '<i class="ph-arrow-counter-clockwise me-1"></i> Confirm Reset';
                    btn.className = 'btn btn-warning';
                }
            }
        </script>
    @endif
@endpush
