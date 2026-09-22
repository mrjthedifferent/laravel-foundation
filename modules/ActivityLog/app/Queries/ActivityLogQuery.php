<?php

namespace Modules\ActivityLog\Queries;

use Illuminate\Database\Eloquent\Collection;
use Mrj\Foundation\Support\QueryBuilder;
use Override;
use OwenIt\Auditing\Models\Audit;

final class ActivityLogQuery extends QueryBuilder
{
    public static function make(): self
    {
        return new self(Audit::query());
    }

    public function search(?string $term): self
    {
        if (filled($term)) {
            $this->whereLike(['old_values', 'new_values', 'user_agent', 'ip_address', 'url'], $term);
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
        // The index shows the audited record's own label, so the morph target comes along:
        // without it the view lazy-loads it, which Laravel refuses when lazy loading is off.
        $this->query->with(['user.roles', 'auditable']);

        return $this;
    }

    public function orderByLatest(): self
    {
        $this->query->orderBy('id', 'desc');

        return $this;
    }

    #[Override]
    public function get(): Collection
    {
        return $this->query->orderByDesc('created_at')->get();
    }
}
