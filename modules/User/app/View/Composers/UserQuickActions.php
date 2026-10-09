<?php

declare(strict_types=1);

namespace Modules\User\View\Composers;

use Mrj\Foundation\Support\QuickActionComposer;
use Override;

/**
 * "Add user" and "Import users" on the dashboard.
 */
final class UserQuickActions extends QuickActionComposer
{
    #[Override]
    public function priority(): int
    {
        return 10;
    }

    #[Override]
    public function actions(): array
    {
        return [
            [
                'label' => __('user::user.quick.add_user'),
                'icon' => 'ph-user-plus',
                'href' => route('admin.users.create'),
                'permission' => 'Create User',
            ],
            [
                'label' => __('user::user.quick.import_users'),
                'icon' => 'ph-upload-simple',
                'href' => route('admin.users.bulk.create'),
                'permission' => 'Import User',
            ],
        ];
    }
}
