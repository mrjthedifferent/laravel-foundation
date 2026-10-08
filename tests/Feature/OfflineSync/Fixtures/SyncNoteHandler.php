<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature\OfflineSync\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mrj\Foundation\Sync\ModelSyncHandler;
use Override;

final class SyncNoteHandler extends ModelSyncHandler
{
    #[Override]
    protected function model(): string
    {
        return SyncNote::class;
    }

    #[Override]
    protected function scope(Builder $query, Authenticatable $user): void
    {
        $query->where('owner_id', $user->getAuthIdentifier());
    }

    #[Override]
    protected function rules(Authenticatable $user, ?Model $existing): array
    {
        return [
            'title' => [$existing === null ? 'required' : 'sometimes', 'string', 'max:100'],
            'body' => ['nullable', 'string'],
        ];
    }

    #[Override]
    protected function creating(Model $model, Authenticatable $user, array $data): void
    {
        $model->setAttribute('owner_id', $user->getAuthIdentifier());
    }
}
