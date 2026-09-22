<?php

namespace Modules\User\Jobs;

use App\Models\User;
use Modules\User\Queries\UserQuery;
use Mrj\Foundation\Support\ExportJob;
use Override;

class UserExportJob extends ExportJob
{
    #[Override]
    protected function buildData(): array
    {
        $sl = 1;

        return UserQuery::make()
            ->withRelations(['roles'])
            ->filterByRole($this->filters['role'] ?? null)
            ->filterByStatus(isset($this->filters['is_active']) ? (bool) $this->filters['is_active'] : null)
            ->search($this->filters['search'] ?? null)
            ->orderByLatest()
            ->get()
            ->map(function (User $user) use (&$sl): array {
                return [
                    'SL' => $sl++,
                    __('foundation::foundation.common.name') => $user->name ?: '',
                    __('user::user.view.col_email') => $user->email ?: '',
                    __('user::user.view.col_phone') => $user->phone ?: '',
                    __('user::user.index.gender_label') => $user->gender ? ucfirst($user->gender->value) : '',
                    __('user::user.index.role_label') => $user->roles->pluck('name')->implode(', '),
                    __('user::user.export.account_status_col') => $user->is_active ? __('foundation::foundation.common.active') : __('foundation::foundation.common.inactive'),
                    __('user::user.export.registration_date_col') => $user->created_at->format('d-M-Y h:i:s A'),
                ];
            })->toArray();
    }

    #[Override]
    protected function title(): string
    {
        return __('user::user.index.breadcrumb');
    }

    #[Override]
    protected function filenamePrefix(): string
    {
        return 'user_list';
    }

    #[Override]
    protected function emptyMessage(): string
    {
        return __('user::user.errors.no_data_found_for_export');
    }
}
