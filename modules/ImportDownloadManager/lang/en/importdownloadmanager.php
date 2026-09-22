<?php

declare(strict_types=1);

return [
    'index' => [
        'breadcrumb' => 'Import Download Manager',
        'title' => 'Import / Download Manager',
        'search_placeholder' => 'Title, type…',
        'type' => 'Type',
        'all_types' => 'All Types',
        'all_statuses' => 'All Statuses',
        'refresh' => 'Refresh',
        'empty' => 'No records found',
        'col_date' => 'Date',
        'col_title' => 'Title',
        'col_remarks' => 'Remarks',
        'download' => 'Download',
        'download_source_file' => 'Download Source File',
        'delete' => 'Delete',
        'delete_confirm' => 'Are you sure you want to delete this record?',
    ],

    'widget' => [
        'title' => 'Import / Download',
        'view_all' => 'View all',
        'total_jobs' => 'Total Jobs',
    ],

    'enums' => [
        'status' => [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ],
        'type' => [
            'import' => 'Import',
            'download' => 'Download',
        ],
    ],

    'flash' => [
        'cannot_delete' => 'Cannot delete a record that is still being processed.',
        'deleted' => 'Record deleted successfully.',
        'file_not_found' => 'File not found.',
    ],

    'errors' => [
        'record_not_found' => 'DownloadImportManager record [:id] not found.',
        'no_records_to_update' => 'No records to update.',
        'default_title' => 'Import/Export',
        'export_complete_title' => ':title Complete',
        'export_complete_body' => 'Your file is ready for download.',
        'export_failed_title' => ':title Failed',
        'export_failed_body_default' => 'An error occurred.',
    ],
];
