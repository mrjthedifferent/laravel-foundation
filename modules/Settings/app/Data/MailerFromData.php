<?php

namespace Modules\Settings\Data;

use Spatie\LaravelData\Data;

class MailerFromData extends Data
{
    public function __construct(
        public string $address,
        public string $name,
    ) {}
}
