<!-- Page header -->
<div class="card mb-3 mx-3 mt-3 shadow-sm border-0">
    <div class="page-header-content d-lg-flex">
        <div class="d-flex align-items-center w-100">
            <div class="breadcrumb py-2 ps-3 flex-grow-1">
                <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">
                    <i class="ph-house"></i>
                </a>
                {{ $breadcrumbs ?? '' }}
            </div>
        </div>
    </div>
</div>
<!-- /page header -->