<?php

namespace Modules\Notification\Enum;

enum NotificationType: string
{
    case Info = 'info';
    case Export = 'export';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Export => 'Export',
            self::Error => 'Error',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
