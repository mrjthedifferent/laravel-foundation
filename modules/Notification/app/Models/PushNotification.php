<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notification\Database\Factories\PushNotificationFactory;
use Mrj\Foundation\Services\FileManagerService;
use Override;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property string|null $url
 * @property string|null $description
 * @property string $recipient_type specific · all · role
 * @property string|null $recipient_role
 * @property int|null $user_id
 * @property array<string, mixed>|null $result
 */
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
        'recipient_role',
        'user_id',
    ];

    #[Override]
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
