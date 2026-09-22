@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">User List</a>
    <span class="breadcrumb-item active">Import Users</span>
@endsection

@section('content')
<form action="{{ route('admin.users.bulk') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <x-page-header
        title="Import Users"
        subtitle="Upload an Excel file to bulk-create users"
        icon="ph-upload-simple"
        :back-url="route('admin.users.index')"
        back-label="Back to List" />

    <x-alert type="info" icon="ph-info">
        <span class="fs-sm">
            <strong>Instructions:</strong> Download the sample file, fill in user details, and upload.
            Required columns: <code>name</code>, <code>email</code>, <code>role</code>.
            Optional: <code>phone</code> (with country code), <code>gender</code>, <code>password</code>, <code>is_active</code>.
        </span>
    </x-alert>

    <x-form-section title="File Upload" icon="ph-file-xls">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.file name="users" label="Upload Excel File" required accept=".xlsx,.xls" />
                <div class="form-text">Accepted formats: .xlsx, .xls</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold fs-sm">Sample Template</label>
                <div>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.bulk.sample') }}">
                        <i class="ph-file-xls me-1"></i> Download Sample
                    </a>
                </div>
                <div class="form-text">Use this template to prepare your user data</div>
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="ph-x me-1"></i> Cancel
        </a>
        <x-primary-button type="submit">
            <i class="ph-upload me-1"></i> Upload Users
        </x-primary-button>
    </div>

</form>
@endsection
