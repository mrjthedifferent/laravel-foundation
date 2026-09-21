<?php

namespace Modules\User\Enum;

/**
 * Document Type Enum
 *
 * Defines supported document types for user verification.
 */
enum DocumentType: string
{
    case Nid = 'nid';
    case Passport = 'passport';
    case DrivingLicense = 'driving_license';
    case BirthCertificate = 'birth_certificate';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Nid => __('National ID Card'),
            self::Passport => __('Passport'),
            self::DrivingLicense => __('Driving License'),
            self::BirthCertificate => __('Birth Certificate'),
        };
    }

    /**
     * Check if document requires back image
     */
    public function requiresBackImage(): bool
    {
        return match ($this) {
            self::Nid, self::DrivingLicense => true,
            default => false,
        };
    }

    /**
     * Check if document has expiry date
     */
    public function hasExpiry(): bool
    {
        return match ($this) {
            self::Passport, self::DrivingLicense => true,
            default => false,
        };
    }

    /**
     * Get validation rules for document number
     */
    public function numberValidationRules(): array
    {
        return match ($this) {
            self::Nid => ['required', 'digits_between:10,17'],
            self::Passport => ['required', 'alpha_num', 'size:9'],
            self::DrivingLicense => ['required', 'alpha_num', 'max:20'],
            self::BirthCertificate => ['nullable', 'alpha_num', 'max:20'],
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for select dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
