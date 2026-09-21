<?php

namespace Mrj\Foundation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class DeviceToken extends Model
{
    protected $fillable = ['user_id', 'token', 'platform', 'last_used_at'];

    #[Override]
    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('foundation.user_model'));
    }
}
