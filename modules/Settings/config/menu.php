<?php

/*
 * Sidebar pages this module contributes. `group` names the parent (see config/sidebar.php);
 * SidebarMenu shows a page only when its route exists and the user holds one of its permissions.
 */

return [
    [
        'group' => 'settings',
        'label' => 'General Settings',
        'icon' => 'ph-sliders',
        'route' => 'admin.settings.index',
        'permissions' => ['Edit System Setting'],
        'order' => 10,
    ],
    [
        'group' => 'settings',
        'label' => 'Privacy Policy',
        'icon' => 'ph-file-text',
        'route' => 'admin.settings.special.privacy_policy',
        'permissions' => ['Edit Special Setting'],
        'order' => 20,
    ],
    [
        'group' => 'settings',
        'label' => 'Terms & Conditions',
        'icon' => 'ph-scroll',
        'route' => 'admin.settings.special.terms_conditions',
        'permissions' => ['Edit Special Setting'],
        'order' => 30,
    ],
    [
        'group' => 'settings',
        'label' => 'SMS Gateways',
        'icon' => 'ph-chat-text',
        'route' => 'admin.settings.special.sms_gateways',
        'permissions' => ['Edit Special Setting'],
        'order' => 40,
    ],
    [
        'group' => 'settings',
        'label' => 'Email Mailers',
        'icon' => 'ph-envelope',
        'route' => 'admin.settings.special.email_mailers',
        'permissions' => ['Edit Special Setting'],
        'order' => 50,
    ],
    [
        'group' => 'settings',
        'label' => 'Notifications',
        'icon' => 'ph-bell-ringing',
        'route' => 'admin.settings.special.notifications',
        'routes' => ['admin.settings.special.update_notifications'],
        'permissions' => ['Edit Special Setting'],
        'order' => 60,
    ],
    [
        'group' => 'settings',
        'label' => 'Firebase',
        'icon' => 'ph-fire-simple',
        'route' => 'admin.settings.special.firebase',
        'permissions' => ['Edit Special Setting'],
        'order' => 70,
    ],
    [
        'group' => 'settings',
        'label' => 'Social Auth',
        'icon' => 'ph-users-three',
        'route' => 'admin.settings.special.social_auth',
        'permissions' => ['Edit Special Setting'],
        'order' => 80,
    ],
    [
        'group' => 'settings',
        'label' => 'Theme',
        'icon' => 'ph-paint-brush',
        'route' => 'admin.settings.special.theme',
        'routes' => ['admin.settings.special.update_theme'],
        'permissions' => ['Edit Special Setting'],
        'order' => 90,
    ],
    [
        'group' => 'settings',
        'label' => 'Manage Settings',
        'icon' => 'ph-gear-six',
        'route' => 'admin.settings.manage',
        'routes' => ['admin.settings.sync', 'admin.settings.create', 'admin.settings.store_new', 'admin.settings.edit', 'admin.settings.update', 'admin.settings.destroy', 'admin.settings.export', 'admin.settings.import_form'],
        'permissions' => ['Developer Setting'],
        'order' => 110,
    ],
];
