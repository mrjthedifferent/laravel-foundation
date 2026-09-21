<?php

namespace Modules\ActivityLog\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

final readonly class ActivityLogQuery
{
    private Builder $query;

    public function __construct()
    {
        $this->query = Audit::query();
    }

    public static function make(): self
    {
        return new self;
    }

    public function search(?string $term): self
    {
        if (filled($term)) {
            $like = '%'.escapeLike($term).'%';

            $this->query->where(function ($q) use ($like): void {
                $q->whereRaw('old_values LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('new_values LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('user_agent LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('ip_address LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('url LIKE ? ESCAPE ?', [$like, '\\']);
            });
        }

        return $this;
    }

    public function filterByEvent(?string $event): self
    {
        if (filled($event)) {
            $this->query->where('event', $event);
        }

        return $this;
    }

    public function filterByAuditableType(?string $type): self
    {
        if (filled($type)) {
            $this->query->where('auditable_type', $type);
        }

        return $this;
    }

    public function filterByUserId(mixed $userId): self
    {
        if (filled($userId)) {
            $this->query->where('user_id', $userId);
        }

        return $this;
    }

    public function filterByDateFrom(?string $date): self
    {
        if (filled($date)) {
            $this->query->whereDate('created_at', '>=', $date);
        }

        return $this;
    }

    public function filterByDateTo(?string $date): self
    {
        if (filled($date)) {
            $this->query->whereDate('created_at', '<=', $date);
        }

        return $this;
    }

    public function withUser(): self
    {
        $this->query->with('user.roles');

        return $this;
    }

    public function orderByLatest(): self
    {
        $this->query->orderBy('id', 'desc');

        return $this;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    public function get(): Collection
    {
        return $this->query->orderByDesc('created_at')->get();
    }
}
