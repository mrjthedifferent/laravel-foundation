<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| User Module Permissions
|--------------------------------------------------------------------------
|
| Define the permissions for this module. Each entry requires:
|   - module_name: The group label shown in the permissions UI
|   - name: The unique permission name
|
*/
return [
    ['module_name' => 'User', 'name' => 'View User'],
    ['module_name' => 'User', 'name' => 'Create User'],
    ['module_name' => 'User', 'name' => 'Edit User'],
    ['module_name' => 'User', 'name' => 'Delete User'],
    ['module_name' => 'User', 'name' => 'User Password Reset'],
    ['module_name' => 'User', 'name' => 'Verify User Contact'],
    ['module_name' => 'User', 'name' => 'Export User'],
    ['module_name' => 'User', 'name' => 'Import User'],
];
