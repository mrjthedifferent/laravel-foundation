<?php

declare(strict_types=1);

return [
    ['module_name' => 'Ledger Management', 'name' => 'View Ledger'],
    // Declared by a tenant module but meant for platform staff only: never seeded in a tenant.
    ['module_name' => 'Ledger Management', 'name' => 'Audit Ledger', 'contexts' => ['central']],
];
