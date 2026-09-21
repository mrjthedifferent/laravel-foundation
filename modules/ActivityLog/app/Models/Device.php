<?php

namespace Modules\ActivityLog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_label',
        'device_type',
        'os',
        'os_version',
        'model',
        'app_version',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
