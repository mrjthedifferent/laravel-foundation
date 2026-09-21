<?php

declare(strict_types=1);

namespace Modules\User\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

/**
 * User Data Transfer Object
 *
 * Pure DTO — carries validated field values between layers.
 * Validation rules live in their respective Form Request classes.
 */
class UserData extends Data
{
    public function __construct(
        #[Email]
        public ?string $email = null,

        public ?string $phone = null,

        #[Min(6)]
        public ?string $password = null,

        public ?string $gender = null,
        public ?string $name = null,
        public mixed $image = null,

        public ?array $roles = [],

        public bool $is_active = true,
    ) {}
}
