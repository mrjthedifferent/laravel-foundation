<?php

declare(strict_types=1);

namespace Modules\User\Data;

use Spatie\LaravelData\Data;

/**
 * User Data Transfer Object
 *
 * Pure DTO — carries validated field values between layers. Every call site
 * builds it via UserData::from(array), always from a Form Request's already-
 * validated() data (or a hand-built array of already-trusted values); it is
 * never resolved directly as a controller parameter, the only way Spatie
 * Data's own validation attributes would actually run. Validation rules
 * live in the Form Request classes, not here.
 */
class UserData extends Data
{
    public function __construct(
        public ?string $email = null,

        public ?string $phone = null,

        public ?string $password = null,

        public ?string $gender = null,
        public ?string $name = null,
        public mixed $image = null,

        public ?array $roles = [],

        public bool $is_active = true,
    ) {}
}
