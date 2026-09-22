<?php

namespace Modules\ActivityLog\Queries;

use Modules\ActivityLog\Models\EmailLog;
use Mrj\Foundation\Support\QueryBuilder;

final class EmailLogQuery extends QueryBuilder
{
    public static function make(): self
    {
        return new self(EmailLog::query());
    }

    public function search(?string $term): self
    {
        if (filled($term)) {
            $this->whereLike(['to_email', 'subject', 'notification'], $term);
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
}
