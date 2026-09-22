<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\ActivityLog\Database\Factories\EmailLogFactory;
use Override;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string $to_email
 * @property string|null $to_name
 * @property array|null $cc
 * @property array|null $bcc
 * @property string|null $from_email
 * @property string|null $from_name
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $mailer
 * @property string|null $notification
 * @property string $status
 * @property string|null $error
 * @property array|null $metadata
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|EmailLog newModelQuery()
 * @method static Builder<static>|EmailLog newQuery()
 * @method static Builder<static>|EmailLog query()
 *
 * @mixin \Eloquent
 */
class EmailLog extends Model
{
    use HasFactory;

    /**
     * HasFactory's default guess is Database\Factories\{model}Factory in the
     * app namespace; this package's factories live per-module instead.
     */
    protected static function newFactory(): EmailLogFactory
    {
        return EmailLogFactory::new();
    }

    protected $fillable = [
        'uuid',
        'to_email',
        'to_name',
        'cc',
        'bcc',
        'from_email',
        'from_name',
        'subject',
        'body',
        'mailer',
        'notification',
        'status',
        'error',
        'metadata',
        'sent_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'cc' => 'array',
            'bcc' => 'array',
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
