<?php

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */

return [
    [
        'group' => 'administration',
        'label' => 'OTP Whitelist',
        'icon' => 'ph-password',
        'route' => 'admin.otp-whitelist.index',
        'routes' => ['admin.otp-whitelist.create', 'admin.otp-whitelist.show', 'admin.otp-whitelist.edit'],
        'permissions' => ['View OTP Whitelist', 'Create OTP Whitelist', 'Edit OTP Whitelist', 'Delete OTP Whitelist'],
        'order' => 50,
    ],
    [
        'group' => 'administration',
        'label' => 'Verification Code History',
        'icon' => 'ph-clock-clockwise',
        'route' => 'admin.otp.history.index',
        'permissions' => ['View Verification Code History'],
        'order' => 60,
    ],
];
