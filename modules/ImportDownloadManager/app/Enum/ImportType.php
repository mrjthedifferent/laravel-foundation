<?php

namespace Modules\ImportDownloadManager\Enum;

enum ImportType: string
{
    case Import = 'import';
    case Download = 'download';

    public function label(): string
    {
        return match ($this) {
            self::Import => __('importdownloadmanager::importdownloadmanager.enums.type.import'),
            self::Download => __('importdownloadmanager::importdownloadmanager.enums.type.download'),
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
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
