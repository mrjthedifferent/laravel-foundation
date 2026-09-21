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

    public static function make(): static
    {
        return new self;
    }

    public function search(?string $term): static
    {
        if (filled($term)) {
            $this->query->where(function ($q) use ($term) {
                $q->whereRaw('old_values LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('new_values LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('user_agent LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('ip_address LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('url LIKE ?', ["%{$term}%"]);
            });
        }

        return $this;
    }

    public function filterByEvent(?string $event): static
    {
        if (filled($event)) {
            $this->query->where('event', $event);
        }

        return $this;
    }

    public function filterByAuditableType(?string $type): static
    {
        if (filled($type)) {
            $this->query->where('auditable_type', $type);
        }

        return $this;
    }

    public function filterByUserId(mixed $userId): static
    {
        if (filled($userId)) {
            $this->query->where('user_id', $userId);
        }

        return $this;
    }

    public function filterByDateFrom(?string $date): static
    {
        if (filled($date)) {
            $this->query->whereDate('created_at', '>=', $date);
        }

        return $this;
    }

    public function filterByDateTo(?string $date): static
    {
        if (filled($date)) {
            $this->query->whereDate('created_at', '<=', $date);
        }

        return $this;
    }

    public function withUser(): static
    {
        $this->query->with('user.roles');

        return $this;
    }

    public function orderByLatest(): static
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
