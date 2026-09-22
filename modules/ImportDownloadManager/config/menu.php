<?php

declare(strict_types=1);

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */
return [
    [
        'group' => 'imports',
        'label' => 'Import / Download Manager',
        'icon' => 'ph-download',
        'route' => 'admin.download.import.manager.index',
        'routes' => ['admin.download.import.status.update'],
        'permissions' => ['Download Import Manager Management'],
        'order' => 180,
    ],
];
