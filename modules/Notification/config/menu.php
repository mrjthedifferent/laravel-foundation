<?php

declare(strict_types=1);

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */
return [
    [
        'group' => 'communications',
        'label' => 'Push Notifications',
        'icon' => 'ph-device-mobile',
        'route' => 'admin.push.notification.index',
        'permissions' => ['View Push Notification'],
        'order' => 30,
    ],
    [
        'group' => 'communications',
        'label' => 'Send Notification',
        'icon' => 'ph-paper-plane-tilt',
        'route' => 'admin.push.notification.create',
        'permissions' => ['Create Push Notification'],
        'order' => 40,
    ],
];
