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
}
