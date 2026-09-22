<?php

namespace Mrj\Foundation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mrj\Foundation\Database\Factories\DeviceTokenFactory;
use Override;

class DeviceToken extends Model
{
    use HasFactory;

    /**
     * HasFactory's default guess is Database\Factories\{model}Factory in the
     * app namespace; the package's factories live in its own namespace.
     */
    protected static function newFactory(): DeviceTokenFactory
    {
        return DeviceTokenFactory::new();
    }

    protected $fillable = ['user_id', 'token', 'platform', 'last_used_at'];

    #[Override]
    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
