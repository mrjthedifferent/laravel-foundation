<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notification\Database\Factories\PushNotificationFactory;
use Mrj\Foundation\Services\FileManagerService;

class PushNotification extends Model
{
    use HasFactory;

    protected static function newFactory(): PushNotificationFactory
    {
        return PushNotificationFactory::new();
    }

    protected $fillable = [
        'title',
        'body',
        'data',
        'image',
        'url',
        'description',
        'result',
        'recipient_type',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'result' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getImageAttribute($value): ?string
    {
        return FileManagerService::getImage($value);
    }

    public function setImageAttribute($value): void
    {
        $this->attributes['image'] = FileManagerService::uploadFile($value, null, 'push-notification');
    }
}
