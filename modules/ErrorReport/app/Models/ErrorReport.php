<?php

namespace Modules\ErrorReport\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'fingerprint',
        'exception_class',
        'message',
        'file',
        'line',
        'trace',
        'request_method',
        'request_path',
        'request_url',
        'user_id',
        'context',
        'occurrences',
        'first_seen_at',
        'last_seen_at',
        'last_notified_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trace' => 'array',
            'context' => 'array',
            'occurrences' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }
}
