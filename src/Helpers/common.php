<?php

use Mrj\Foundation\Services\FileManagerService;

if (! function_exists('tableDataInfo')) {

    function tableDataInfo($table): void
    {
        $table->softDeletes();
        $table->foreignId('created_by')->nullable()->comment('0 for system');
        $table->foreignId('updated_by')->nullable()->comment('0 for system');
    }
}

if (! function_exists('getUrlFromPath')) {

    function getUrlFromPath($path): string
    {
        return FileManagerService::getImage($path);
    }
}

if (! function_exists('status')) {

    function status()
    {
        return [
            'Active' => 'Active',
            'Deactivate' => 'Deactivate',
        ];
    }
}
if (! function_exists('integerStatus')) {

    function integerStatus()
    {
        return [
            '1' => 'Active',
            '0' => 'Inactive',
        ];
    }
}

if (! function_exists('getParPagePaginate')) {

    /**
     * The page-size picker's options, e.g. for a <select>: ['10' => '10', ...].
     */
    function getParPagePaginate(): array
    {
        $options = array_map('strval', config('foundation.pagination.options', [10, 25, 50, 100]));

        return array_combine($options, $options);
    }
}

if (! function_exists('perPage')) {

    /**
     * The page size for a listing that offers the fixed picker
     * (foundation.pagination.options): the request's ?per_page if it is one
     * of those options, otherwise $default (or foundation.pagination.default).
     */
    function perPage(?int $default = null): int
    {
        $default ??= (int) config('foundation.pagination.default', 10);
        $val = (int) request('per_page', $default);

        return in_array($val, config('foundation.pagination.options', [10, 25, 50, 100]), true) ? $val : $default;
    }
}

if (! function_exists('escapeLike')) {

    /**
     * Escape a user-supplied search term for safe use inside a LIKE pattern.
     * Without this, a term containing % or _ changes what the wildcard match
     * does rather than being searched for literally — '%' matches everything,
     * turning a "search" box into a way to dump a whole table's rows, and a
     * pattern built from many wildcards can force a full table scan.
     */
    function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}

if (! function_exists('cappedPerPage')) {

    /**
     * Clamp a requested per-page count for endpoints that accept an arbitrary
     * value rather than picking from perPage()'s fixed list. Without a ceiling,
     * ?per_page=999999999 turns a paginated log/report listing into an
     * unbounded query.
     */
    function cappedPerPage(int $value, ?int $max = null): int
    {
        $max ??= (int) config('foundation.pagination.max', 100);

        return max(1, min($max, $value));
    }
}

if (! function_exists('snakeCase')) {
    function snakeCase($string)
    {
        $string = preg_replace('/[^A-Za-z0-9]/', '', $string);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}

if (! function_exists('form_old_key')) {

    /**
     * A bracketed field name (roles[], option_keys[]) as the dot-notation key
     * old() understands — Laravel's own old() takes dot paths, not the raw
     * HTML array-name syntax a <select multiple> or a repeated-row input
     * submits under.
     */
    function form_old_key(string $name): string
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $name);
    }
}

if (! function_exists('display_label')) {

    /**
     * A label that comes from config or the database (a menu item, permission,
     * setting group), translated through lang/{locale}.json when a translation
     * exists. Unlike __() it never returns an array: a value that happens to
     * equal a lang file name ("auth", "validation") comes back unchanged.
     */
    function display_label(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $line = __($value);

        return is_string($line) ? $line : $value;
    }
}
