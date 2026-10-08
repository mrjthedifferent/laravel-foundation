<?php

declare(strict_types=1);

namespace Mrj\Foundation\Sync;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Mrj\Foundation\Contracts\SyncHandler;
use Override;

/**
 * Standard handler for a Syncable model. A subclass names the model, scopes
 * what a user may see, and gives validation rules:
 *
 *     final class FarmSyncHandler extends ModelSyncHandler
 *     {
 *         protected function model(): string { return Farm::class; }
 *         protected function scope(Builder $query, Authenticatable $user): void { $query->whereIn('id', ...); }
 *         protected function rules(Authenticatable $user, ?Model $existing): array { return ['name' => ['required', 'string']]; }
 *     }
 *
 * Writes are authorised through the model's policy (`create`, `update`,
 * `delete`), only `$fillable` attributes are taken from the client, and a
 * change based on an older version than the server's is a conflict. Override
 * creating() to set server-owned attributes (owner, farm id) on new rows, or
 * apply() for a different conflict policy.
 *
 * @api
 */
abstract class ModelSyncHandler implements SyncHandler
{
    /**
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    /**
     * Restrict the query to rows the user may see.
     *
     * @param  Builder<Model>  $query
     */
    abstract protected function scope(Builder $query, Authenticatable $user): void;

    /**
     * Validation rules for pushed data. `$existing` is null for a new row.
     *
     * @return array<string, mixed>
     */
    abstract protected function rules(Authenticatable $user, ?Model $existing): array;

    /**
     * Set server-owned attributes on a row about to be created.
     *
     * @param  array<string, mixed>  $data
     */
    protected function creating(Model $model, Authenticatable $user, array $data): void {}

    #[Override]
    public function visibleTo(Authenticatable $user): Builder
    {
        $class = $this->model();
        $query = $class::query()->withoutGlobalScopes();
        $this->scope($query, $user);

        return $query;
    }

    #[Override]
    public function toSync(Model $model): array
    {
        return $model->attributesToArray();
    }

    #[Override]
    public function apply(Authenticatable $user, SyncOperation $op): SyncResult
    {
        $class = $this->model();
        $gate = Gate::forUser($user);

        $existing = $class::query()->withoutGlobalScopes()->find($op->id);

        if ($existing !== null && ! $this->visibleTo($user)->whereKey($op->id)->exists()) {
            // Someone else's row: answer as if it did not exist.
            return SyncResult::rejected($op, ['id' => [__('foundation::foundation.offline_sync.not_found')]]);
        }

        $deleted = $existing !== null && method_exists($existing, 'trashed') && $existing->trashed();

        if ($op->isDelete()) {
            if ($existing === null || $deleted) {
                return SyncResult::applied($op, (int) ($existing?->getAttribute('version') ?? 0));
            }

            if ($this->isStale($existing, $op)) {
                return SyncResult::conflict($op, $this->toSync($existing));
            }

            if (! $gate->allows('delete', $existing)) {
                return SyncResult::rejected($op, ['id' => [__('foundation::foundation.offline_sync.forbidden')]]);
            }

            $existing->delete();

            return SyncResult::applied($op, (int) $existing->getAttribute('version'));
        }

        if ($deleted) {
            return SyncResult::conflict($op, null);
        }

        if ($existing !== null && $this->isStale($existing, $op)) {
            return SyncResult::conflict($op, $this->toSync($existing));
        }

        $validator = Validator::make($op->data, $this->rules($user, $existing));

        if ($validator->fails()) {
            return SyncResult::rejected($op, $validator->errors()->toArray());
        }

        $data = $validator->validated();

        if ($existing === null) {
            if (! $gate->allows('create', $class)) {
                return SyncResult::rejected($op, ['id' => [__('foundation::foundation.offline_sync.forbidden')]]);
            }

            $model = new $class;
            $model->setAttribute($model->getKeyName(), $op->id);
            $model->fill($data);
            $this->creating($model, $user, $data);
            $model->save();

            return SyncResult::applied($op, (int) $model->getAttribute('version'));
        }

        if (! $gate->allows('update', $existing)) {
            return SyncResult::rejected($op, ['id' => [__('foundation::foundation.offline_sync.forbidden')]]);
        }

        $existing->fill($data);

        if ($existing->isDirty()) {
            $existing->save();
        }

        return SyncResult::applied($op, (int) $existing->getAttribute('version'));
    }

    /**
     * The client based its change on an older version than the server holds.
     */
    protected function isStale(Model $existing, SyncOperation $op): bool
    {
        return $op->version !== null && (int) $existing->getAttribute('version') !== $op->version;
    }
}
