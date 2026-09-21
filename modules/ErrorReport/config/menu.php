<?php

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */

return [
    [
        'group' => 'administration',
        'label' => 'Error Reports',
        'icon' => 'ph-bug',
        'route' => 'admin.error-reports.index',
        'routes' => ['admin.error-reports.show'],
        'permissions' => ['View Error Report'],
        'order' => 150,
    ],
    [
        'group' => 'administration',
        'label' => 'Error Report Settings',
        'icon' => 'ph-gear',
        'route' => 'admin.error-reports.settings.index',
        'permissions' => ['Edit Error Report Settings'],
        'order' => 160,
    ],
];
