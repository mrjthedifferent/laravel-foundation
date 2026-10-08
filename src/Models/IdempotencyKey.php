<?php

declare(strict_types=1);

namespace Mrj\Foundation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * A stored response for an `Idempotency-Key` request (see the `idempotent`
 * middleware). Expired rows are removed by `php artisan model:prune`.
 *
 * @property int $id
 * @property string $scope
 * @property string $key
 * @property string $method
 * @property string $path
 * @property string $request_hash
 * @property int|null $status_code
 * @property array<string, list<string>>|null $response_headers
 * @property string|null $response_body
 * @property Carbon $expires_at
 *
 * @api
 */
class IdempotencyKey extends Model
{
    use MassPrunable;

    protected $fillable = [
        'scope',
        'key',
        'method',
        'path',
        'request_hash',
        'status_code',
        'response_headers',
        'response_body',
        'expires_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response_headers' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now());
    }

    public function isComplete(): bool
    {
        return $this->status_code !== null;
    }
}
