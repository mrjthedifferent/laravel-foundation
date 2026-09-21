<?php

namespace Modules\Otp\Enum;

enum ContactType: string
{
    case Email = 'email';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Email => __('Email'),
            self::Phone => __('Phone'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Detect the contact type from the contact value itself.
     * Emails contain '@'; everything else is treated as a phone number.
     */
    public static function detect(string $contact): self
    {
        return str_contains($contact, '@') ? self::Email : self::Phone;
    }
}
