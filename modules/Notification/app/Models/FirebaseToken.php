<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notification\Database\Factories\FirebaseTokenFactory;

class FirebaseToken extends Model
{
    use HasFactory;

    /**
     * HasFactory's default guess is Database\Factories\{model}Factory in the
     * app namespace; this package's factories live per-module instead.
     */
    protected static function newFactory(): FirebaseTokenFactory
    {
        return FirebaseTokenFactory::new();
    }

    protected $fillable = [
        'token',
        'device_id',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to tokens belonging to a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
