<?php

namespace Modules\Otp\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Otp\Database\Factories\OtpWhitelistFactory;
use Modules\Otp\Enum\ContactType;
use Mrj\Foundation\Support\PhoneNumber;
use OwenIt\Auditing\Contracts\Auditable;

class OtpWhitelist extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'recipient_type',
        'recipient',
        'fixed_otp',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'recipient_type' => ContactType::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): OtpWhitelistFactory
    {
        return OtpWhitelistFactory::new();
    }

    /**
     * Store phone recipients canonically (E.164) so they match the OTP contact;
     * emails pass through unchanged.
     */
    protected function recipient(): Attribute
    {
        return Attribute::set(fn ($value) => $value === null ? null : PhoneNumber::normalizeContact((string) $value));
    }

    public static function findByRecipient(ContactType|string $recipientType, string $recipient): ?self
    {
        $type = $recipientType instanceof ContactType ? $recipientType->value : $recipientType;

        return static::where('recipient_type', $type)
            ->where('recipient', PhoneNumber::normalizeContact($recipient))
            ->where('is_active', true)
            ->first();
    }
}
