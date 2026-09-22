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
                    'Name' => $user->name ?: '',
                    'Email' => $user->email ?: '',
                    'Phone' => $user->phone ?: '',
                    'Gender' => $user->gender ? ucfirst($user->gender->value) : '',
                    'Role' => $user->roles->pluck('name')->implode(', '),
                    'Account Status' => $user->is_active ? 'Active' : 'Inactive',
                    'Registration Date' => $user->created_at->format('d-M-Y h:i:s A'),
                ];
            })->toArray();
    }

    #[Override]
    protected function title(): string
    {
        return 'User List';
    }

    #[Override]
    protected function filenamePrefix(): string
    {
        return 'user_list';
    }

    #[Override]
    protected function emptyMessage(): string
    {
        return 'No Data Found For Export';
    }
}
