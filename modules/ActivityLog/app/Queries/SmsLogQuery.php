<?php

namespace Modules\ActivityLog\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ActivityLog\Models\SmsLog;

final readonly class SmsLogQuery
{
    private Builder $query;

    public function __construct()
    {
        $this->query = SmsLog::query();
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
                $q->whereRaw('phone LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('message LIKE ? ESCAPE ?', [$like, '\\']);
            });
        }

        return $this;
    }

    public function filterByStatus(?string $status): self
    {
        if (filled($status)) {
            $this->query->where('status', $status);
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

    public function orderByLatest(): self
    {
        $this->query->orderByDesc('id');

        return $this;
    }

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return $this->query->paginate($perPage ?? (int) config('foundation.pagination.default', 10))->withQueryString();
    }
}
