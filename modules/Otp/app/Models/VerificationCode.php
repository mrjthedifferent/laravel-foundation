<?php

namespace Modules\Otp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Modules\Otp\Database\Factories\VerificationCodeFactory;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Support\OtpHasher;
use Mrj\Foundation\Contracts\HasSmsContact;
use Override;

/**
 * @property int $id
 * @property string|null $code Plain code; only on rows written before 1.8.
 * @property string|null $code_hash Keyed hash of the code (see OtpHasher).
 * @property ContactType $contact_type
 * @property string $contact
 * @property Carbon|null $expires_at
 * @property bool $is_verified
 * @property int $attempts
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VerificationCode extends Model implements HasSmsContact
{
    use HasFactory, Notifiable;

    /**
     * Maximum number of failed verification attempts allowed before a code
     * is invalidated. Mitigates brute-force guessing of short numeric codes.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * The plain code, available only in memory on the instance that generated
     * it (for the notification and the opt-in debug API response). It is
     * never written to the database.
     */
    public ?string $plainCode = null;

    protected $fillable = [
        'code',
        'code_hash',
        'contact_type',
        'contact',
        'expires_at',
        'is_verified',
        'attempts',
    ];

    protected $hidden = ['code', 'code_hash'];

    /**
     * Assigning `code` stores only its keyed hash. The plain value stays on
     * the instance as `plainCode` and the `code` column is left empty.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (self $verificationCode): void {
            $plain = $verificationCode->getAttribute('code');

            if ($plain !== null && $verificationCode->isDirty('code')) {
                $verificationCode->plainCode = (string) $plain;
                $verificationCode->code_hash = OtpHasher::hash((string) $verificationCode->contact, (string) $plain);
                $verificationCode->setAttribute('code', null);
            }
        });
    }

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

    /**
     * Whether the given code matches this record. Rows written before 1.8
     * hold the plain code instead of a hash and are compared directly.
     */
    public function matches(string $code): bool
    {
        if ($this->code_hash !== null) {
            return hash_equals($this->code_hash, OtpHasher::hash((string) $this->contact, $code));
        }

        return $this->code !== null && hash_equals((string) $this->code, $code);
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
