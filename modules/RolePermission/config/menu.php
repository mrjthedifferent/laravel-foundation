<?php

declare(strict_types=1);

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */
return [
    [
        'group' => 'administration',
        'label' => 'Roles',
        'icon' => 'ph-shield',
        'route' => 'admin.role.index',
        'routes' => ['admin.role.assign.permission.get', 'admin.role.clone'],
        'permissions' => ['Create Role', 'View Role', 'Edit Role', 'Delete Role', 'Assign Permission'],
        'order' => 20,
    ],
    [
        'group' => 'administration',
        'label' => 'Permissions',
        'icon' => 'ph-lock-key',
        'route' => 'admin.permissions.manage',
        'routes' => ['admin.permission.sync', 'admin.permission.delete', 'admin.permission.bulk-delete', 'admin.permission.store', 'admin.permission.roles'],
        'permissions' => ['Assign Permission'],
        'order' => 30,
    ],
];
