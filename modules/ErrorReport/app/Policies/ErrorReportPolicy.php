<?php

namespace Modules\ErrorReport\Policies;

use App\Models\User;
use Modules\ErrorReport\Models\ErrorReport;

class ErrorReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View Error Report');
    }

    public function view(User $user, ErrorReport $errorReport): bool
    {
        return $user->can('View Error Report');
    }

    public function resolve(User $user, ErrorReport $errorReport): bool
    {
        return $user->can('Resolve Error Report');
    }

    public function delete(User $user, ErrorReport $errorReport): bool
    {
        return $user->can('Delete Error Report');
    }
}
