<?php

namespace Modules\User\Enum;

/**
 * Account management action types.
 *
 * Replaces the magic strings 'reset' / 'delete' used in ManageUserAccountAction
 * and ManageUserAccountRequest with a type-safe enum.
 */
enum AccountAction: string
{
    case Reset = 'reset';
    case Delete = 'delete';

    public function label(): string
    {
        return match ($this) {
            self::Reset => 'Reset',
            self::Delete => 'Delete',
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
