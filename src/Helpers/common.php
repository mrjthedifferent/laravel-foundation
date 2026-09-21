<?php

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Mrj\Foundation\Services\FileManagerService;

if (! function_exists('tableDataInfo')) {

    function tableDataInfo($table)
    {
        $table->softDeletes();
        $table->foreignId('created_by')->nullable()->comment('0 for system');
        $table->foreignId('updated_by')->nullable()->comment('0 for system');
    }
}

if (! function_exists('ajaxResponse')) {

    function ajaxResponse($code, $message = 'The given data was invalid.', $errors = null, $data = null): JsonResponse
    {
        $status = (int) $code;

        if ($status >= 200 && $status < 300) {
            return JsonResponseFactory::success($message, $data, $status);
        }

        if (! is_null($errors) && ! is_object($errors)) {
            $errors = (object) $errors;
        }

        return JsonResponseFactory::error($message, $errors, $status);
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

if (! function_exists('getCommonStatus')) {

    function getCommonStatus()
    {

        return [
            'Active' => 'Active',
            'Inactive' => 'Inactive',
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

if (! function_exists('getIntegerMonth')) {

    function getIntegerMonth()
    {
        return [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];
    }
}

if (! function_exists('getLast11Digit')) {

    function getLast11Digit($number)
    {
        $phoneNumber = trim($number);
        // Remove any non-numeric characters from the phone number
        $numericOnly = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If the phone number starts with '880', remove that prefix
        if (Str::startsWith($numericOnly, '880')) {
            $numericOnly = substr($numericOnly, 3);
        }

        // Ensure the phone number starts with '0'
        if (! Str::startsWith($numericOnly, '0')) {
            $numericOnly = '0'.$numericOnly;
        }

        // Now you have the formatted phone number
        return $numericOnly;
    }
}

if (! function_exists('engToBangla')) {

    function engToBangla($number)
    {
        $search_array = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $replace_array = ['১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯', '০'];

        return str_replace($search_array, $replace_array, $number);
    }
}

if (! function_exists('currency_number')) {
    function currency_number($number)
    {
        return number_format($number, 2, '.', ',');
    }
}

if (! function_exists('snakeCase')) {
    function snakeCase($string)
    {
        $string = preg_replace('/[^A-Za-z0-9]/', '', $string);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}

if (! function_exists('isImage')) {
    function isImage($url): bool
    {
        if (! is_string($url)) {
            return false;
        }
        if (! isUrl($url)) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $headers = curl_exec($ch);
        curl_close($ch);

        if ($headers === false) {
            return false;
        }

        return str_contains($headers, 'Content-Type: image/');
    }
}

if (! function_exists('isUrl')) {
    function isUrl($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (! function_exists('parseDate')) {
    function parseDate($val, $format = 'j-M-y', $outFormat = 'Y-m-d')
    {
        if ($val instanceof DateTimeInterface) {
            return $val->format($outFormat);
        }

        $val = trim((string) $val);
        if (empty($val)) {
            return null;
        }

        try {
            return Carbon::createFromFormat($format, $val)->format($outFormat);
        } catch (Exception $e) {
            try {
                return Carbon::parse($val)->format($outFormat);
            } catch (Exception $e) {
                return null;
            }
        }
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
