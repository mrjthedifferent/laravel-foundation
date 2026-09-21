<?php

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */

return [
    [
        'group' => 'administration',
        'label' => 'Backups',
        'icon' => 'ph-database',
        'route' => 'admin.backups.index',
        'permissions' => ['View Backup', 'Create Backup', 'Delete Backup', 'Download Backup', 'Cleanup Backup'],
        'order' => 170,
    ],
];
