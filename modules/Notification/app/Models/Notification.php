<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Database\Factories\NotificationFactory;

class Notification extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Scope to a specific notifiable model.
     */
    public function scopeForNotifiable(Builder $query, object $notifiable): Builder
    {
        return $query
            ->where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->id);
    }

    /**
     * Scope to unread notifications only.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to read notifications only.
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    // ── State helpers ─────────────────────────────────────────────────────────

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        if (! is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Determine if the notification has been read.
     */
    public function read(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Determine if the notification has not been read.
     */
    public function unread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Get the notification title from data.
     */
    public function getTitleAttribute(): string
    {
        $data = is_array($this->data) ? $this->data : [];

        return $data['title'] ?? 'Notification';
    }

    /**
     * Get the notification body from data.
     */
    public function getBodyAttribute(): string
    {
        $data = is_array($this->data) ? $this->data : [];

        return $data['body'] ?? '';
    }

    /**
     * Get the nested data payload (e.g. url for exports).
     *
     * @return array<string, mixed>
     */
    public function getDataPayloadAttribute(): array
    {
        $data = is_array($this->data) ? $this->data : [];
        $nested = $data['data'] ?? [];

        return is_array($nested) ? $nested : [];
    }

    /**
     * Get download URL for export notifications, or null.
     */
    public function getDownloadUrlAttribute(): ?string
    {
        $payload = $this->data_payload;

        return $payload['url'] ?? null;
    }
}
