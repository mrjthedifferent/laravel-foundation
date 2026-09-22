@extends('backupcleanup::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('backupcleanup::backupcleanup.index.breadcrumb') }}</span>
@endsection

@section('content')
    <x-table-view-pagination title="{{ __('backupcleanup::backupcleanup.index.breadcrumb') }}" :data="$files" empty-icon="ph-archive"
        empty-message="{{ __('backupcleanup::backupcleanup.index.empty') }}">
        <x-slot name="actions">
            <x-table-actions>
                @can('Cleanup Backup')
                    <x-table-action class="btn-warning swal-post" icon="ph-eraser" title="{{ __('backupcleanup::backupcleanup.index.run_cleanup') }}"
                        data-url="{{ route('admin.backups.cleanup') }}"
                        data-text="{{ __('backupcleanup::backupcleanup.index.run_cleanup_confirm') }}" />
                @endcan
                @can('Create Backup')
                    <x-table-action class="btn-primary swal-post" icon="ph-plus" title="{{ __('backupcleanup::backupcleanup.index.create_backup') }}"
                        data-url="{{ route('admin.backups.store') }}"
                        data-text="{{ __('backupcleanup::backupcleanup.index.create_backup_confirm') }}" />
                @endcan
            </x-table-actions>
        </x-slot>

        <thead>
            <tr>
                <th style="width:44px">#</th>
                <th>{{ __('backupcleanup::backupcleanup.index.col_file_name') }}</th>
                <th>{{ __('backupcleanup::backupcleanup.index.col_size') }}</th>
                <th>{{ __('backupcleanup::backupcleanup.index.col_created_at') }}</th>
                <th>{{ __('backupcleanup::backupcleanup.index.col_disk') }}</th>
                <th class="text-end" style="width:60px">{{ __('foundation::foundation.common.action') }}</th>
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
                                    <i class="ph-download-simple me-2"></i>{{ __('backupcleanup::backupcleanup.index.download') }}
                                </x-dropdown-link>
                            @endcan
                            @can('Delete Backup')
                                <div class="dropdown-divider"></div>
                                <x-dropdown-link :url="route('admin.backups.destroy', ['filename' => $file['filename']])"
                                    class="text-danger swal-delete" data-text="{{ __('backupcleanup::backupcleanup.index.delete_confirm') }}" data-method="DELETE"><i class="ph-trash me-2"></i> {{ __('backupcleanup::backupcleanup.index.delete') }}
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
        {!! __('backupcleanup::backupcleanup.index.stored_on', [
            'disk' => '<strong>'.e(config('backup.backup.destination.disks')[0] ?? 'local').'</strong>',
            'name' => '<strong>'.e(config('backup.backup.name', config('app.name'))).'</strong>',
            'count' => '<strong>'.e($files->count()).'</strong>',
        ]) !!}
    </p>
@endsection
