<?php

namespace Modules\Otp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Modules\Otp\Database\Factories\VerificationCodeFactory;
use Modules\Otp\Enum\ContactType;
use Mrj\Foundation\Contracts\HasSmsContact;
use Override;

class VerificationCode extends Model implements HasSmsContact
{
    use HasFactory, Notifiable;

    /**
     * Maximum number of failed verification attempts allowed before a code
     * is invalidated. Mitigates brute-force guessing of short numeric codes.
     */
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'code',
        'contact_type',
        'contact',
        'expires_at',
        'is_verified',
        'attempts',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'contact_type' => ContactType::class,
            'expires_at' => 'datetime',
            'is_verified' => 'boolean',
            'attempts' => 'integer',
        ];
    }

    protected static function newFactory(): VerificationCodeFactory
    {
        return VerificationCodeFactory::new();
    }

    public function routeNotificationForMail(): string
    {
        return $this->contact;
    }

    #[Override]
    public function getSmsContact(): ?string
    {
        return $this->contact;
    }

    public function scopeActive($query): void
    {
        $query->where('expires_at', '>', now())->where('is_verified', false);
    }

    public function scopeCode($query, string $code): void
    {
        $query->where('code', $code);
    }

    public function scopeContact($query, string $contact): void
    {
        $query->where('contact', $contact);
    }

    public function markAsVerified(): void
    {
        $this->update(['is_verified' => true]);
    }

    /**
     * Record a failed verification attempt and invalidate the code once the
     * maximum number of attempts has been reached.
     */
    public function registerFailedAttempt(): void
    {
        $attempts = $this->attempts + 1;

        $this->update([
            'attempts' => $attempts,
            // Force-expire the code when the attempt ceiling is hit so it can
            // no longer be guessed even before its natural expiry.
            'expires_at' => $attempts >= self::MAX_ATTEMPTS ? now()->subSecond() : $this->expires_at,
        ]);
    }

    public function attemptsExhausted(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }
}
