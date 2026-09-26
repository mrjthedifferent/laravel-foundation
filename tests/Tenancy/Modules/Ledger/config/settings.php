<?php

declare(strict_types=1);

return [
    'ledger_setting' => ['group' => 'Ledger', 'value' => '1', 'type' => 'integer', 'description' => 'Tenant fixture setting.'],
    // Declared by a tenant module but only meaningful centrally: never seeded in a tenant.
    'ledger_central_setting' => ['group' => 'Ledger', 'value' => '1', 'type' => 'integer', 'description' => 'Central-only.', 'contexts' => ['central']],
];
