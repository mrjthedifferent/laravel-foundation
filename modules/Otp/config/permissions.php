<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Otp Module Permissions
|--------------------------------------------------------------------------
|
| Define the permissions for this module. Each entry requires:
|   - module_name: The group label shown in the permissions UI
|   - name: The unique permission name
|
*/
return [
    ['module_name' => 'Otp', 'name' => 'View OTP Whitelist'],
    ['module_name' => 'Otp', 'name' => 'Create OTP Whitelist'],
    ['module_name' => 'Otp', 'name' => 'Edit OTP Whitelist'],
    ['module_name' => 'Otp', 'name' => 'Delete OTP Whitelist'],
    ['module_name' => 'Otp', 'name' => 'View Verification Code History'],
];
