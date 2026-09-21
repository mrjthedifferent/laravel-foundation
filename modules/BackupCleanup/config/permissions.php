<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BackupCleanup Module Permissions
|--------------------------------------------------------------------------
|
| Define the permissions for this module. Each entry requires:
|   - module_name: The group label shown in the permissions UI
|   - name: The unique permission name
|
*/
return [
    ['module_name' => 'Backup Management', 'name' => 'View Backup'],
    ['module_name' => 'Backup Management', 'name' => 'Create Backup'],
    ['module_name' => 'Backup Management', 'name' => 'Delete Backup'],
    ['module_name' => 'Backup Management', 'name' => 'Download Backup'],
    ['module_name' => 'Backup Management', 'name' => 'Cleanup Backup'],
];
