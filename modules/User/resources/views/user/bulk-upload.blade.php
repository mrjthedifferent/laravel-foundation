@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">{{ __('user::user.index.breadcrumb') }}</a>
    <span class="breadcrumb-item active">{{ __('user::user.bulk_upload.breadcrumb') }}</span>
@endsection

@section('content')
<form action="{{ route('admin.users.bulk') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <x-page-header
        title="{{ __('user::user.bulk_upload.breadcrumb') }}"
        subtitle="{{ __('user::user.bulk_upload.subtitle') }}"
        icon="ph-upload-simple"
        :back-url="route('admin.users.index')"
        back-label="{{ __('user::user.bulk_upload.back_label') }}" />

    <x-alert type="info" icon="ph-info">
        <span class="text-sm">
            <strong>{{ __('user::user.bulk_upload.instructions_label') }}</strong> {!! __('user::user.bulk_upload.instructions') !!}
        </span>
    </x-alert>

    <x-form-section title="{{ __('user::user.bulk_upload.section_file_upload') }}" icon="ph-file-xls">
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <x-form.file name="users" label="{{ __('user::user.bulk_upload.upload_label') }}" required accept=".xlsx,.xls" />
                <div class="form-text">{{ __('user::user.bulk_upload.accepted_formats') }}</div>
            </div>
            <div class="col-span-12 md:col-span-6">
                <label class="form-label">{{ __('user::user.bulk_upload.sample_template_label') }}</label>
                <div>
                    <a class="btn btn-sm btn-light" href="{{ route('admin.users.bulk.sample') }}">
                        <i class="ph-file-xls"></i>{{ __('user::user.bulk_upload.download_sample') }}
                    </a>
                </div>
                <div class="form-text">{{ __('user::user.bulk_upload.sample_hint') }}</div>
            </div>
        </div>
    </x-form-section>

    <div class="flex justify-between items-center">
        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
            <i class="ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
        </a>
        <x-primary-button type="submit">
            <i class="ph-upload"></i>{{ __('user::user.bulk_upload.submit') }}
        </x-primary-button>
    </div>

</form>
@endsection
