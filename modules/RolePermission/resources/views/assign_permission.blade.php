@extends('rolepermission::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.role.index') }}" class="breadcrumb-item">{{ __('rolepermission::rolepermission.index.breadcrumb') }}</a>
    <span class="breadcrumb-item active">{{ __('rolepermission::rolepermission.assign_permission.breadcrumb', ['role' => $role->name]) }}</span>
@endsection

@section('content')
<form action="{{ route('admin.role.assign.permission', $role->id) }}" id="updatePermission" method="POST">
    @csrf

    <x-page-header
        title="{{ __('rolepermission::rolepermission.index.assign_permissions') }}"
        subtitle="{{ __('rolepermission::rolepermission.assign_permission.subtitle', ['role' => $role->name]) }}"
        icon="ph-shield-check"
        :back-url="route('admin.role.index')"
        back-label="{{ __('rolepermission::rolepermission.assign_permission.back_to_roles') }}">
        <x-slot name="actions">
            <label class="d-flex align-items-center gap-2 mb-0 me-1 cursor-pointer">
                <input type="checkbox" class="form-check-input mt-0" id="checkAll">
                <span class="fs-sm fw-semibold">{{ __('rolepermission::rolepermission.assign_permission.check_all') }}</span>
            </label>
            <x-primary-button id="submit-button" class="px-4">
                <i class="ph-floppy-disk me-1"></i>{{ __('rolepermission::rolepermission.assign_permission.save_permissions') }}
            </x-primary-button>
        </x-slot>
    </x-page-header>

    <div class="row g-3">
        @foreach($all_permissions as $key => $permission)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header py-2 d-flex align-items-center justify-content-between bg-body-tertiary border-bottom">
                        <span class="fw-semibold fs-sm text-uppercase" style="letter-spacing:.04em;">{{ display_label($key) }}</span>
                        <input type="checkbox"
                               class="form-check-input checkByModule"
                               data-id="{{ str_replace(' ', '-', $key) }}"
                               title="{{ __('rolepermission::rolepermission.assign_permission.toggle_module', ['module' => display_label($key)]) }}">
                    </div>
                    <div class="card-body py-2">
                        @foreach($permission as $item)
                            <div class="d-flex align-items-center gap-2 py-1 border-bottom border-opacity-25">
                                <input class="form-check-input inputCheckbox {{ str_replace(' ', '-', $key) }}"
                                       type="checkbox"
                                       name="permissions[]"
                                       value="{{ $item->name }}"
                                       {{ $role->hasPermissionTo($item->name) ? 'checked' : '' }}
                                       id="checkBox{{ $item->id }}">
                                <label class="form-check-label fs-sm" for="checkBox{{ $item->id }}">
                                    {{ display_label($item->name) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-end mt-3">
        <x-primary-button class="px-5">
            <i class="ph-floppy-disk me-1"></i>{{ __('rolepermission::rolepermission.assign_permission.save_permissions') }}
        </x-primary-button>
    </div>

</form>
@endsection

@push('scripts')
    <script>
        $(document).on('change', '#checkAll', function () {
            $('.inputCheckbox').prop('checked', this.checked);
        });
        $(document).on('change', '.checkByModule', function () {
            var cls = $(this).data('id');
            $('.' + cls).prop('checked', this.checked);
        });
    </script>
@endpush
