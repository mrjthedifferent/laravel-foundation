<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Sign-in panel
|--------------------------------------------------------------------------
|
| The brand panel beside the sign-in form (and the other guest pages: password
| reset, two-factor, email verification). Change it here; no Blade needs editing.
|
| Text values are passed through __(), so each can be a translation key
| ('foundation::foundation.auth.tagline') or plain text ('Run payroll in minutes').
| :app is replaced with the application's name. Leave a value null to use the
| package's translation.
|
| Settings → Login page in the admin stores its values over these at run time:
| a saved value wins, a missing one leaves this file's.
|
| Publish to change it: php artisan vendor:publish --tag=foundation-login
| A published file is merged over this one key by key.
|
*/

return [

    // The headline. null = foundation::foundation.auth.tagline
    'headline' => null,

    // The line under it. null = foundation::foundation.auth.tagline_meta
    'subheadline' => null,

    // The feature bullets, in order: a Phosphor 2 icon (weight class + icon) and a text.
    // [] hides the list; an item without a text is skipped; a missing icon is ph ph-check.
    'highlights' => [
        ['icon' => 'ph ph-shield-check', 'text' => 'foundation::foundation.auth.highlight_access'],
        ['icon' => 'ph ph-clock-counter-clockwise', 'text' => 'foundation::foundation.auth.highlight_audit'],
        ['icon' => 'ph ph-chart-line-up', 'text' => 'foundation::foundation.auth.highlight_insight'],
    ],

    // The mark beside the name:
    //   'auto'     the logo from Settings if one is uploaded, else the app's monogram
    //   'monogram' always the monogram (first letter of the app name)
    //   'none'     the name only
    'mark' => 'auto',

    // Show the application's name beside the mark.
    'show_name' => true,

    // The decorative graphic, drawn in the brand colours:
    //   'bars' rising bars and soft shapes · 'glow' soft shapes only · 'none' the gradient alone
    'graphic' => 'bars',

];
