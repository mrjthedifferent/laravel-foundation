<?php

/*
|--------------------------------------------------------------------------
| Settings Module Permissions
|--------------------------------------------------------------------------
|
| Define the permissions for this module. Each entry requires:
|   - module_name: The group label shown in the permissions UI
|   - name: The unique permission name
|
*/

return [
    ['module_name' => 'Settings', 'name' => 'Edit System Setting'],
    ['module_name' => 'Settings', 'name' => 'Edit Special Setting'],
    // Developer Setting is used for developer to manage the settings (create, edit, delete)
    ['module_name' => 'Settings', 'name' => 'Developer Setting'],
];
