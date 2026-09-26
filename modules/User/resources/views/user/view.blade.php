@extends('user::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('user::user.index.breadcrumb') }}</a></span>
    <span class="breadcrumb-item active">{{ $user->name }}</span>
@endsection

@section('content')
    {{-- Header Card --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $user->image }}" class="fd-avatar fd-avatar-lg" alt="{{ $user->name }}">
                        <div class="min-width-0">
                            <h5 class="mb-1">{{ $user->name }}</h5>
                            <p class="mb-2 text-muted fs-sm">
                                @if ($user->email)
                                    <i class="ph-envelope me-1"></i> {{ $user->email }}
                                    @if ($user->email_verified_at)
                                        <i class="ph-check-circle text-success ms-1" title="{{ __('user::user.view.col_email_verified') }}"></i>
                                    @endif
                                @endif
                                @if ($user->phone)
                                    @if ($user->email)
                                        <span class="mx-2">·</span>
                                    @endif
                                    <i class="ph-phone me-1"></i> {{ $user->phone }}
                                    @if ($user->phone_verified_at)
                                        <i class="ph-check-circle text-success ms-1" title="{{ __('user::user.view.col_phone_verified') }}"></i>
                                    @endif
                                @endif
                            </p>
                            <div class="d-flex flex-wrap gap-1">
                                @if ($user->isSuperAdmin())
                                    <span class="badge bg-danger"><i class="ph-crown"></i>{{ __('user::user.common.super_admin') }}</span>
                                @endif
                                @foreach ($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                @endforeach
                                <x-status-badge :active="$user->is_active" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        @can('Edit User')
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary">
                                <i class="ph-pencil-simple"></i>{{ __('user::user.view.edit_user') }}
                            </a>
                        @endcan
                        @can('User Password Reset')
                            <a href="{{ route('admin.user.password.reset', $user->id) }}"
                                class="btn btn-sm btn-light swal-confirm"
                                data-text="{{ __('user::user.view.reset_password_confirm') }}">
                                <i class="ph-key"></i>{{ __('user::user.view.reset_password') }}
                            </a>
                        @endcan
                        @if (config('foundation.two_factor.enabled') && $user->hasTwoFactorEnabled())
                            @can('resetPassword', $user)
                                <a href="{{ route('admin.users.two-factor.reset', $user->id) }}"
                                    class="btn btn-sm btn-light swal-post" data-method="DELETE"
                                    data-text="{{ __('user::user.two_factor.reset_confirm') }}">
                                    <i class="ph-shield-slash"></i>{{ __('user::user.two_factor.reset_button') }}
                                </a>
                            @endcan
                        @endif
                        @can('impersonate', $user)
                            <a href="{{ route('admin.users.impersonate', $user->id) }}"
                                class="btn btn-sm btn-light swal-post"
                                data-text="{{ __('user::user.view.impersonate_confirm', ['name' => $user->name]) }}">
                                <i class="ph-user-switch"></i>{{ __('user::user.view.impersonate') }}
                            </a>
                        @endcan
                    </div>
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
                        <i class="ph-user me-1"></i> {{ __('user::user.view.tab_personal') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab"
                        data-bs-target="#documents" type="button" role="tab" aria-selected="false">
                        <i class="ph-files me-1"></i> {{ __('user::user.view.tab_documents') }}
                        <span class="badge badge-count ms-1">{{ $user->documents->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="login-history-tab" data-bs-toggle="tab"
                        data-bs-target="#login-history" type="button" role="tab" aria-selected="false">
                        <i class="ph-clock-counter-clockwise me-1"></i> {{ __('user::user.view.tab_login_history') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="user-tab-content">

                {{-- Tab 1: Personal Information --}}
                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <dl class="fd-dl">
                                <dt>{{ __('user::user.view.col_id') }}</dt>
                                <dd>{{ $user->id }}</dd>

                                <dt>{{ __('user::user.view.col_uuid') }}</dt>
                                <dd><span class="font-monospace fs-sm">{{ $user->uuid ?? 'N/A' }}</span></dd>

                                <dt>{{ __('user::user.view.col_full_name') }}</dt>
                                <dd>{{ $user->name }}</dd>

                                <dt>{{ __('user::user.view.col_email') }}</dt>
                                <dd>
                                    {{ $user->email ?? 'N/A' }}
                                    @if ($user->email && $user->email_verified_at)
                                        <i class="ph-check-circle text-success ms-1" title="{{ __('user::user.common.verified') }}"></i>
                                    @elseif ($user->email)
                                        <i class="ph-x-circle text-warning ms-1" title="{{ __('user::user.view.not_verified_badge') }}"></i>
                                    @endif
                                </dd>

                                <dt>{{ __('user::user.view.col_gender') }}</dt>
                                <dd>{{ ucfirst($user->gender?->value ?? 'N/A') }}</dd>

                                <dt>{{ __('user::user.view.col_roles') }}</dt>
                                <dd>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($user->roles as $role)
                                            <span class="badge bg-primary">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-muted">{{ __('user::user.view.no_roles') }}</span>
                                        @endforelse
                                    </div>
                                </dd>

                                <dt>{{ __('foundation::foundation.common.status') }}</dt>
                                <dd><x-status-badge :active="$user->is_active" /></dd>

                                @if ($user->provider)
                                    <dt>{{ __('user::user.view.col_social_login') }}</dt>
                                    <dd><span class="badge bg-info">{{ ucfirst($user->provider) }}</span></dd>
                                @endif
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="fd-dl">
                                <dt>{{ __('user::user.view.col_last_login') }}</dt>
                                <dd>
                                    @if ($user->latestLogin)
                                        {{ $user->latestLogin->logged_in_at->format('d M Y h:i A') }}
                                        <div class="text-muted fs-xs">{{ $user->latestLogin->logged_in_at->diffForHumans() }}</div>
                                    @else
                                        <span class="text-muted">{{ __('user::user.view.never_logged_in') }}</span>
                                    @endif
                                </dd>

                                <dt>{{ __('user::user.view.col_created') }}</dt>
                                <dd>
                                    {{ $user->created_at->format('d M Y h:i A') }}
                                    <div class="text-muted fs-xs">{{ $user->created_at->diffForHumans() }}</div>
                                </dd>

                                <dt>{{ __('user::user.view.col_updated') }}</dt>
                                <dd>
                                    {{ $user->updated_at->format('d M Y h:i A') }}
                                    <div class="text-muted fs-xs">{{ $user->updated_at->diffForHumans() }}</div>
                                </dd>

                                <dt>{{ __('user::user.view.col_email_verified') }}</dt>
                                <dd>
                                    @if ($user->email_verified_at)
                                        <span class="badge bg-success">{{ __('user::user.common.verified') }}</span>
                                        <div class="text-muted fs-xs">{{ $user->email_verified_at->format('d M Y') }}</div>
                                    @else
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge bg-warning">{{ __('user::user.view.not_verified_badge') }}</span>
                                            @can('Verify User Contact')
                                                @if ($user->email)
                                                    <form method="POST" action="{{ route('admin.users.verify.email', $user) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-light swal-confirm"
                                                            data-text="{{ __('user::user.view.verify_email_confirm') }}">
                                                            <i class="ph-check-circle"></i>{{ __('user::user.view.verify_email') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    @endif
                                </dd>

                                <dt>{{ __('user::user.view.col_phone') }}</dt>
                                <dd>{{ $user->phone ?? '—' }}</dd>

                                @if ($user->phone)
                                    <dt>{{ __('user::user.view.col_phone_verified') }}</dt>
                                    <dd>
                                        @if ($user->phone_verified_at)
                                            <span class="badge bg-success">{{ __('user::user.common.verified') }}</span>
                                            <div class="text-muted fs-xs">{{ $user->phone_verified_at->format('d M Y') }}</div>
                                        @else
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="badge bg-warning">{{ __('user::user.view.not_verified_badge') }}</span>
                                                @can('Verify User Contact')
                                                    <form method="POST" action="{{ route('admin.users.verify.phone', $user) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-light swal-confirm"
                                                            data-text="{{ __('user::user.view.verify_phone_confirm') }}">
                                                            <i class="ph-check-circle"></i>{{ __('user::user.view.verify_phone') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        @endif
                                    </dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Documents --}}
                <div class="tab-pane fade" id="documents" role="tabpanel">
                    <div class="table-responsive mb-4">
                        <table class="table table-hover table-borderless table-nowrap align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('user::user.view.col_type') }}</th>
                                    <th>{{ __('user::user.view.col_number') }}</th>
                                    <th>{{ __('user::user.view.col_files') }}</th>
                                    <th>{{ __('user::user.view.col_expiry_date') }}</th>
                                    <th>{{ __('user::user.view.col_uploaded_at') }}</th>
                                    @can('Edit User')
                                        <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($user->documents as $document)
                                    <tr>
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td>{{ $document->document_type->label() }}</td>
                                        <td>{{ $document->document_number ?? 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{{ \Mrj\Foundation\Services\FileManagerService::getFile($document->file_path) }}"
                                                    target="_blank" class="btn btn-sm btn-light">
                                                    <i class="ph-eye"></i>{{ __('user::user.view.doc_front') }}
                                                </a>
                                                @if ($document->back_file_path)
                                                    <a href="{{ \Mrj\Foundation\Services\FileManagerService::getFile($document->back_file_path) }}"
                                                        target="_blank" class="btn btn-sm btn-light">
                                                        <i class="ph-eye"></i>{{ __('user::user.view.doc_back') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            {{ $document->expiry_date ? $document->expiry_date->format('d M Y') : 'N/A' }}
                                            @if ($document->expiry_date && $document->expiry_date->isPast())
                                                <span class="badge bg-danger ms-1">{{ __('user::user.view.expired_badge') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $document->created_at->format('d M Y') }}</td>
                                        @can('Edit User')
                                            <td class="text-end">
                                                <x-dropdown-menu>
                                                    <button type="button" class="dropdown-item text-danger swal-delete"
                                                        data-url="{{ route('admin.users.documents.destroy', [$user->id, $document->id]) }}"
                                                        data-text="{{ __('user::user.view.delete_document_confirm') }}">
                                                        <i class="ph-trash"></i>{{ __('foundation::foundation.common.delete') }}
                                                    </button>
                                                </x-dropdown-menu>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-0">
                                            <div class="fd-empty">
                                                <span class="fd-empty-icon"><i class="ph-folder-open"></i></span>
                                                <div class="fd-empty-title">{{ __('user::user.view.no_documents') }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Upload Form --}}
                    @can('Edit User')
                        <div class="border-top pt-4">
                            <div class="fd-overline mb-3">{{ __('user::user.view.upload_document_heading') }}</div>
                            <form action="{{ route('admin.users.documents.store', $user->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <x-form.select name="document_type" label="{{ __('user::user.view.document_type_label') }}" required :options="['' => __('user::user.view.select_document_type')] + collect(\Modules\User\Enum\DocumentType::cases())->mapWithKeys(fn($t) => [$t->value => $t->label()])->toArray()" :selected="null" />
                                    </div>
                                    <div class="col-md-4">
                                        <x-form.input name="document_number" label="{{ __('user::user.view.document_number_label') }}" placeholder="{{ __('user::user.view.document_number_placeholder') }}" />
                                    </div>
                                    <div class="col-md-4">
                                        <x-form.input type="date" name="expiry_date" label="{{ __('user::user.view.col_expiry_date') }}" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-form.file name="file" label="{{ __('user::user.view.front_side_file_label') }}" required accept="image/*,.pdf" />
                                        <div class="form-text">{{ __('user::user.view.accepted_files_hint') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <x-form.label for="back_file">{{ __('user::user.view.back_side_file_label') }}</x-form.label>
                                        <span class="text-muted fw-normal">{{ __('user::user.view.optional_label') }}</span>
                                        <x-form.file name="back_file" accept="image/*,.pdf" />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <x-primary-button type="submit">
                                        <i class="ph-upload"></i>{{ __('user::user.view.upload_document_submit') }}
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
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('user::user.view.col_device') }}</th>
                                    <th>{{ __('user::user.view.col_browser') }}</th>
                                    <th>{{ __('user::user.view.col_platform') }}</th>
                                    <th>{{ __('user::user.view.col_ip_address') }}</th>
                                    <th>{{ __('user::user.view.col_logged_in') }}</th>
                                    <th>{{ __('user::user.view.col_logged_out') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loginHistory as $history)
                                    <tr>
                                        <td class="text-muted">{{ $loginHistory->firstItem() + $loop->index }}</td>
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
                                            <div class="text-muted fs-xs">{{ $history->logged_in_at->diffForHumans() }}</div>
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
                                        <td colspan="7" class="p-0">
                                            <div class="fd-empty">
                                                <span class="fd-empty-icon"><i class="ph-clock-counter-clockwise"></i></span>
                                                <div class="fd-empty-title">{{ __('user::user.view.no_login_history') }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($loginHistory->total() > 0)
                        <div class="fd-table-foot">
                            <span>
                                {{ __('foundation::foundation.table.showing', ['first' => $loginHistory->firstItem(), 'last' => $loginHistory->lastItem(), 'total' => number_format($loginHistory->total())]) }}
                            </span>
                            {{ $loginHistory->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Manage Account (non-production only) --}}
    @if (!app()->environment('production'))
        @can('Delete User')
            <div class="card mt-3" id="manage-account">
                <div class="card-header">
                    <span class="fd-icon-tile fd-icon-tile-sm is-danger"><i class="ph-warning-octagon"></i></span>
                    <h2 class="card-title">{{ __('user::user.view.manage_account_heading') }}</h2>
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
                                            <i class="ph-arrow-counter-clockwise text-warning me-1"></i> {{ __('user::user.view.reset_account_label') }}
                                        </label>
                                        <div class="text-muted fs-xs ms-4">{{ __('user::user.view.reset_account_hint') }}</div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action"
                                            id="actionDelete" value="delete"
                                            onchange="updateManageWarning()">
                                        <label class="form-check-label" for="actionDelete">
                                            <i class="ph-trash text-danger me-1"></i> {{ __('user::user.view.delete_account_label') }}
                                        </label>
                                        <div class="text-muted fs-xs ms-4">{{ __('user::user.view.delete_account_hint') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="manageResetWarning" class="alert alert-warning mb-0">
                                    <i class="ph-warning"></i>
                                    <div><strong>{{ __('user::user.view.warning_label') }}</strong> {{ __('user::user.view.reset_warning_text') }}</div>
                                </div>
                                <div id="manageDeleteWarning" class="alert alert-danger mb-0 d-none">
                                    <i class="ph-warning-octagon"></i>
                                    <div><strong>{{ __('user::user.view.danger_label') }}</strong> {{ __('user::user.view.delete_warning_text') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" id="manageConfirmButton" class="btn btn-warning swal-confirm"
                                    data-text="{{ __('user::user.view.manage_account_confirm') }}">
                                <i class="ph-arrow-counter-clockwise"></i>{{ __('user::user.view.confirm_reset') }}
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
                    btn.innerHTML = '<i class="ph-trash"></i>{{ __('user::user.view.confirm_delete') }}';
                    btn.className = 'btn btn-danger';
                } else {
                    btn.innerHTML = '<i class="ph-arrow-counter-clockwise"></i>{{ __('user::user.view.confirm_reset') }}';
                    btn.className = 'btn btn-warning';
                }
            }
        </script>
    @endif
@endpush
