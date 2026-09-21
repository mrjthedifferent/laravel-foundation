<?php

namespace Modules\User\Enum;

/**
 * Gender Enum
 *
 * Provides type-safe gender values with localization support.
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Male => __('Male'),
            self::Female => __('Female'),
            self::Other => __('Other'),
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for select dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
