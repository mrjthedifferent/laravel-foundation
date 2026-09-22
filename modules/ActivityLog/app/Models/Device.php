<?php

namespace Modules\ActivityLog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ActivityLog\Database\Factories\DeviceFactory;

class Device extends Model
{
    use HasFactory;

    /**
     * HasFactory's default guess is Database\Factories\{model}Factory in the
     * app namespace; this package's factories live per-module instead.
     */
    protected static function newFactory(): DeviceFactory
    {
        return DeviceFactory::new();
    }

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
