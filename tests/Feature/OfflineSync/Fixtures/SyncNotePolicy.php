<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature\OfflineSync\Fixtures;

use App\Models\User;

final class SyncNotePolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, SyncNote $note): bool
    {
        return $note->owner_id === $user->id;
    }

    public function delete(User $user, SyncNote $note): bool
    {
        return $note->owner_id === $user->id && $note->title !== 'locked';
    }
}
