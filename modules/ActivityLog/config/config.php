<?php

declare(strict_types=1);

return [
    'name' => 'ActivityLog',

    /*
    | Request/response logging (LogRequestResponse) masks the values of keys that
    | look secret (password, token, secret, otp, …). List more here, e.g. personal
    | data your project handles: ['msisdn', 'national_id']. A key matches exactly,
    | case-insensitive, at any depth; its whole value (even an array) is masked.
    */
    'sensitive_keys' => [],
];
