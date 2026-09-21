<?php

use Modules\Notification\Support\NotificationToggleRegistry;

/*
|--------------------------------------------------------------------------
| Otp Module Settings
|--------------------------------------------------------------------------
|
| Define settings seeded into the settings table for this module.
| Each key maps to a setting record. Leave empty if this module
| requires no application settings.
|
*/

return array_merge([
    'otp_digit_length' => [
        'group' => 'OTP',
        'value' => '6',
        'type' => 'integer',
        'description' => 'Number of digits in the OTP code (e.g. 4 or 6).',
    ],
    'otp_expiry_minutes' => [
        'group' => 'OTP',
        'value' => '10',
        'type' => 'integer',
        'description' => 'Number of minutes before an OTP code expires.',
    ],
    'phone_country_codes' => [
        'group' => 'OTP',
        'value' => '+880',
        'type' => 'multi-select',
        'options' => json_encode([
            '+1' => 'USA / Canada (+1)',
            '+7' => 'Russia (+7)',
            '+20' => 'Egypt (+20)',
            '+27' => 'South Africa (+27)',
            '+33' => 'France (+33)',
            '+34' => 'Spain (+34)',
            '+39' => 'Italy (+39)',
            '+44' => 'United Kingdom (+44)',
            '+49' => 'Germany (+49)',
            '+52' => 'Mexico (+52)',
            '+55' => 'Brazil (+55)',
            '+60' => 'Malaysia (+60)',
            '+61' => 'Australia (+61)',
            '+62' => 'Indonesia (+62)',
            '+63' => 'Philippines (+63)',
            '+65' => 'Singapore (+65)',
            '+66' => 'Thailand (+66)',
            '+81' => 'Japan (+81)',
            '+82' => 'South Korea (+82)',
            '+86' => 'China (+86)',
            '+91' => 'India (+91)',
            '+92' => 'Pakistan (+92)',
            '+234' => 'Nigeria (+234)',
            '+880' => 'Bangladesh (+880)',
            '+966' => 'Saudi Arabia (+966)',
            '+971' => 'UAE (+971)',
        ]),
        'description' => 'Allowed phone country codes for OTP. Leave empty to allow all international numbers.',
        'is_required' => false,
    ],
], NotificationToggleRegistry::settingsFor(require __DIR__.'/notification_toggles.php'));
