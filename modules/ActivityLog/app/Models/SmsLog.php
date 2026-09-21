<?php

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $phone
 * @property string $message
 * @property string $status
 * @property string|null $response
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SmsLog extends Model
{
    protected $fillable = [
        'phone',
        'message',
        'status',
        'response',
    ];
}
