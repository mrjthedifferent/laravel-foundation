<?php

namespace Modules\ImportDownloadManager\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Mrj\Foundation\Enum\PaginationEnum;

final readonly class DownloadImportQuery
{
    public function __construct(private Builder $query) {}

    public static function make(): self
    {
        return new self(DownloadImportManager::query()->with('user:id,name,email'));
    }

    public function forUser(int $userId): self
    {
        return new self($this->query->where('user_id', $userId));
    }

    public function filterByStatus(?ImportStatus $status): self
    {
        if ($status === null) {
            return $this;
        }

        return new self($this->query->where('status', $status->value));
    }

    public function filterByType(?ImportType $type): self
    {
        if ($type === null) {
            return $this;
        }

        return new self($this->query->where('type', $type->value));
    }

    public function search(?string $term): self
    {
        if (blank($term)) {
            return $this;
        }

        $like = '%'.escapeLike($term).'%';

        return new self(
            $this->query->where(function (Builder $q) use ($like) {
                $q->whereRaw('title LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('type LIKE ? ESCAPE ?', [$like, '\\']);
            })
        );
    }

    public function orderByLatest(): self
    {
        return new self($this->query->orderByDesc('id'));
    }

    public function paginate(int $perPage = PaginationEnum::DEFAULT_LIST): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    public function findByIds(array $ids): Collection
    {
        return $this->query->whereIn('id', $ids)->get();
    }
}
