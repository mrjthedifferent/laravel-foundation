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

    function getParPagePaginate(): array
    {
        return ['10' => '10', '25' => '25', '50' => '50', '100' => '100'];
    }
}

if (! function_exists('perPage')) {

    function perPage(int $default = 10): int
    {
        $val = (int) request('per_page', $default);

        return in_array($val, [10, 25, 50, 100]) ? $val : $default;
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
    function cappedPerPage(int $value, int $max = 100): int
    {
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

if (! function_exists('enum_value')) {

    /**
     * The scalar behind an enum, for anywhere a raw value is required.
     *
     * Written for konekt/html form binding. `Form::model()` makes the builder read the
     * attribute itself, and it casts that to a string to decide which option is selected
     * — which is fatal against an enum-cast column, since a backed enum cannot be cast to
     * a string. Passing the value through here keeps such a select rendering:
     *
     *     {!! Form::select('type', $types, enum_value($payComponent->type), [...]) !!}
     *
     * Anything that is not an enum is handed back untouched, so it is always safe to
     * wrap a bound value even when the column's cast may change later.
     */
    function enum_value(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        // A pure enum has no backing scalar; its name is the only stable identifier.
        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
    }
}
