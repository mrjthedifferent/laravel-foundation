<?php

namespace Modules\ActivityLog\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ActivityLog\Models\EmailLog;

final readonly class EmailLogQuery
{
    private Builder $query;

    public function __construct()
    {
        $this->query = EmailLog::query();
    }

    public static function make(): static
    {
        return new self;
    }

    public function search(?string $term): static
    {
        if (filled($term)) {
            $this->query->where(function ($q) use ($term) {
                $q->where('to_email', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%")
                    ->orWhere('notification', 'like', "%{$term}%");
            });
        }

        return $this;
    }

    public function filterByStatus(?string $status): static
    {
        if (filled($status)) {
            $this->query->where('status', $status);
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

    public function orderByLatest(): static
    {
        $this->query->orderByDesc('id');

        return $this;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }
}
