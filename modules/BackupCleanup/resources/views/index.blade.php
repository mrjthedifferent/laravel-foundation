@extends('backupcleanup::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Backup Management</span>
@endsection

@section('content')
    <x-table-view-pagination title="Backup Files" :data="$files" empty-icon="ph-archive"
        empty-message="No backup files found">
        <x-slot name="actions">
            <x-table-actions>
                @can('Cleanup Backup')
                    <x-table-action class="btn-warning swal-post" icon="ph-eraser" title="Run Cleanup"
                        data-url="{{ route('admin.backups.cleanup') }}"
                        data-text="This will remove old backup files based on the cleanup strategy. Are you sure?" />
                @endcan
                @can('Create Backup')
                    <x-table-action class="btn-primary swal-post" icon="ph-plus" title="Create Backup"
                        data-url="{{ route('admin.backups.store') }}"
                        data-text="This will create a new database backup now. Continue?" />
                @endcan
            </x-table-actions>
        </x-slot>

        <thead>
            <tr>
                <th style="width:44px">#</th>
                <th>File Name</th>
                <th>Size</th>
                <th>Created At</th>
                <th>Disk</th>
                <th class="text-end" style="width:60px">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($files as $index => $file)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <i class="ph-file-zip text-muted me-1"></i>
                        <span class="text-break fs-sm">{{ $file['filename'] }}</span>
                    </td>
                    <td class="text-nowrap fs-sm">
                        @php $mb = $file['size'] / 1048576; @endphp
                        {{ $mb >= 1 ? number_format($mb, 2) . ' MB' : number_format($file['size'] / 1024, 2) . ' KB' }}
                    </td>
                    <td class="text-nowrap fs-sm">
                        {{ \Carbon\Carbon::createFromTimestamp($file['date'])->format('d M Y, h:i A') }}</td>
                    <td>
                        <span
                            class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ $file['disk'] }}</span>
                    </td>
                    <td class="text-end">
                        <x-dropdown-menu>
                            @can('Download Backup')
                                <x-dropdown-link :url="route('admin.backups.download', ['filename' => $file['filename']])">
                                    <i class="ph-download-simple me-2"></i>Download
                                </x-dropdown-link>
                            @endcan
                            @can('Delete Backup')
                                <div class="dropdown-divider"></div>
                                <x-dropdown-link :url="route('admin.backups.destroy', ['filename' => $file['filename']])"
                                    class="text-danger swal-delete" data-text="Are you sure you want to permanently delete
                                    this backup file?" data-method="DELETE"><i class="ph-trash me-2"></i> Delete
                                </x-dropdown-link>
                            @endcan
                        </x-dropdown-menu>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table-view-pagination>

    <p class="text-muted fs-xs mt-2">
        <i class="ph-info me-1"></i>
        Stored on <strong>{{ config('backup.backup.destination.disks')[0] ?? 'local' }}</strong> disk
        in <strong>{{ config('backup.backup.name', config('app.name')) }}</strong>.
        Total: <strong>{{ $files->count() }}</strong> file(s).
    </p>
@endsection
