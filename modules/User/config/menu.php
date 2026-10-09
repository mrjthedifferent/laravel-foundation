<?php

declare(strict_types=1);

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */
return [
    [
        'group' => 'administration',
        'label' => 'Users',
        'icon' => 'ph ph-users-four',
        'route' => 'admin.users.index',
        'routes' => ['admin.users.create', 'admin.users.show', 'admin.users.edit'],
        'permissions' => ['View User'],
        'order' => 10,
    ],
    [
        'group' => 'administration',
        'label' => 'Deletion requests',
        'icon' => 'ph ph-user-minus',
        'route' => 'admin.deletion-requests.index',
        'permissions' => ['Review Account Deletion'],
        'order' => 12,
    ],
];
