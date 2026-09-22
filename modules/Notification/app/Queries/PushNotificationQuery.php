<?php

namespace Modules\Notification\Queries;

use Modules\Notification\Models\PushNotification;
use Mrj\Foundation\Support\QueryBuilder;

final class PushNotificationQuery extends QueryBuilder
{
    public static function make(): self
    {
        return new self(PushNotification::query()->with('user:id,name,email'));
    }

    public function search(?string $search): self
    {
        if (filled($search)) {
            $this->whereLike(['title', 'body'], $search);
        }

        return $this;
    }

    public function orderByLatest(): self
    {
        $this->query->latest('id');

        return $this;
    }

    public function count(): int
    {
        return $this->query->count();
    }
}
