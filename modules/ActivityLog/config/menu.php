<?php

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */

return [
    [
        'group' => 'administration',
        'label' => 'Activity Logs',
        'icon' => 'ph-activity',
        'route' => 'admin.activity-logs.index',
        'routes' => ['admin.activity-logs.show', 'admin.track-ip'],
        'permissions' => ['View Activity Log', 'Delete Activity Log'],
        'order' => 110,
    ],
    [
        'group' => 'administration',
        'label' => 'SMS Logs',
        'icon' => 'ph-chat-text',
        'route' => 'admin.sms-logs.index',
        'permissions' => ['View SMS Log', 'Delete SMS Log'],
        'order' => 120,
    ],
    [
        'group' => 'administration',
        'label' => 'Email Logs',
        'icon' => 'ph-envelope',
        'route' => 'admin.email-logs.index',
        'routes' => ['admin.email-logs.show'],
        'permissions' => ['View Email Log', 'Delete Email Log'],
        'order' => 130,
    ],
    [
        'group' => 'administration',
        'label' => 'System Logs',
        'icon' => 'ph-terminal',
        'url' => '/log-viewer',
        'target' => '_blank',
        'permissions' => ['View Logs'],
        'order' => 140,
    ],
];
