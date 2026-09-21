<?php

namespace Modules\Notification\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\Models\PushNotification;

final readonly class PushNotificationQuery
{
    public function __construct(private Builder $query) {}

    public static function make(): self
    {
        return new self(PushNotification::query()->with('user:id,name,email'));
    }

    public function search(?string $search): self
    {
        if (filled($search)) {
            return new self(
                $this->query->where(function (Builder $q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                })
            );
        }

        return $this;
    }

    public function orderByLatest(): self
    {
        return new self($this->query->latest('id'));
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    public function get(): Collection
    {
        return $this->query->get();
    }

    public function count(): int
    {
        return $this->query->count();
    }
}
