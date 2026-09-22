<?php

namespace Modules\ImportDownloadManager\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Mrj\Foundation\Support\QueryBuilder;

final class DownloadImportQuery extends QueryBuilder
{
    public static function make(): self
    {
        return new self(DownloadImportManager::query()->with('user:id,name,email'));
    }

    public function forUser(int $userId): self
    {
        $this->query->where('user_id', $userId);

        return $this;
    }

    public function filterByStatus(?ImportStatus $status): self
    {
        if ($status !== null) {
            $this->query->where('status', $status->value);
        }

        return $this;
    }

    public function filterByType(?ImportType $type): self
    {
        if ($type !== null) {
            $this->query->where('type', $type->value);
        }

        return $this;
    }

    public function search(?string $term): self
    {
        if (filled($term)) {
            $this->whereLike(['title', 'type'], $term);
        }

        return $this;
    }

    public function orderByLatest(): self
    {
        $this->query->orderByDesc('id');

        return $this;
    }

    public function findByIds(array $ids): Collection
    {
        return $this->query->whereIn('id', $ids)->get();
    }
}
