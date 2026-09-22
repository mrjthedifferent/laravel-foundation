<?php

declare(strict_types=1);

return [
    'index' => [
        'breadcrumb' => 'Backup Management',
        'empty' => 'No backup files found',
        'run_cleanup' => 'Run Cleanup',
        'run_cleanup_confirm' => 'This will remove old backup files based on the cleanup strategy. Are you sure?',
        'create_backup' => 'Create Backup',
        'create_backup_confirm' => 'This will create a new database backup now. Continue?',
        'col_file_name' => 'File Name',
        'col_size' => 'Size',
        'col_created_at' => 'Created At',
        'col_disk' => 'Disk',
        'download' => 'Download',
        'delete' => 'Delete',
        'delete_confirm' => 'Are you sure you want to permanently delete this backup file?',
        'stored_on' => 'Stored on :disk disk in :name. Total: :count file(s).',
    ],

    'stat' => [
        'last_backup' => 'Last backup',
        'never' => 'Never',
        'none_yet' => 'No backup has run yet',
    ],

    'widget' => [
        'title' => 'Backup',
        'view_all' => 'View all',
        'files_label' => 'Backup Files',
        'last_backup' => 'Last Backup',
        'none' => 'No backups found',
    ],

    'flash' => [
        'queued' => 'Backup has been queued and will complete shortly.',
        'file_not_found' => 'Backup file not found.',
        'deleted' => 'Backup file deleted successfully.',
        'cleanup_queued' => 'Backup cleanup has been queued and will complete shortly.',
    ],

    'errors' => [
        'file_not_found_detail' => 'Backup file not found: :path',
    ],
];
