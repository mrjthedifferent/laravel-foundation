<?php

declare(strict_types=1);

namespace Modules\User\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\User\Enum\DeletionStatus;
use Override;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * One request to delete an account, and what became of it. Kept after the account is
 * anonymized: it is the record that the person asked for the deletion and who reviewed it.
 *
 * @property int $id
 * @property int $user_id
 * @property DeletionStatus $status
 * @property string $source
 * @property Carbon $requested_at
 * @property Carbon|null $scheduled_for
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $reason
 * @property Carbon|null $completed_at
 * @property-read User $user
 * @property-read User|null $reviewer
 */
class AccountDeletionRequest extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = ['user_id', 'status', 'source', 'requested_at', 'scheduled_for', 'reviewed_by', 'reviewed_at', 'reason', 'completed_at'];

    #[Override]
    protected function casts(): array
    {
        return [
            'status' => DeletionStatus::class,
            'requested_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', DeletionStatus::open());
    }
}
