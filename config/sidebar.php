<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Sidebar parents
|--------------------------------------------------------------------------
|
| Two levels only: a parent is a business area, a page sits directly under it.
| Each module declares its pages in its config/menu.php, naming the parent; this
| file fixes the order and look of the parents. A parent flagged `single` is a
| plain link to its one page rather than a submenu. A parent with no visible
| page is not rendered. See Mrj\Foundation\Support\SidebarMenu.
|
| Publish to change it: php artisan vendor:publish --tag=foundation-sidebar
| A published file replaces this list as a whole.
|
*/

return [
    'groups' => [
        'reports' => ['label' => 'Reports', 'icon' => 'ph-chart-bar'],
        'communications' => ['label' => 'Communications', 'icon' => 'ph-bell'],
        'administration' => ['label' => 'Administration', 'icon' => 'ph-shield'],
        'settings' => ['label' => 'Settings', 'icon' => 'ph-gear'],
        'imports' => ['label' => 'Import / Download Manager', 'icon' => 'ph-download', 'single' => true],
    ],
];
