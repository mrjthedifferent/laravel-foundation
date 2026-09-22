<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\ActivityLog\Database\Factories\SmsLogFactory;

/**
 * @property int $id
 * @property string $phone
 * @property string $message
 * @property string $status
 * @property string|null $response
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|SmsLog newModelQuery()
 * @method static Builder<static>|SmsLog newQuery()
 * @method static Builder<static>|SmsLog query()
 * @method static Builder<static>|SmsLog whereCreatedAt($value)
 * @method static Builder<static>|SmsLog whereId($value)
 * @method static Builder<static>|SmsLog whereMessage($value)
 * @method static Builder<static>|SmsLog wherePhone($value)
 * @method static Builder<static>|SmsLog whereResponse($value)
 * @method static Builder<static>|SmsLog whereStatus($value)
 * @method static Builder<static>|SmsLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SmsLog extends Model
{
    use HasFactory;

    /**
     * HasFactory's default guess is Database\Factories\{model}Factory in the
     * app namespace; this package's factories live per-module instead.
     */
    protected static function newFactory(): SmsLogFactory
    {
        return SmsLogFactory::new();
    }

    protected $fillable = [
        'phone',
        'message',
        'status',
        'response',
    ];
}
