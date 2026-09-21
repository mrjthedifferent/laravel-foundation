<?php

/*
| Files `php artisan foundation:sync` manages in a project.
|
| from  path under this sync/ directory (a file, or a directory copied recursively)
| to    path relative to the project root
| mode  overwrite  the foundation owns the file; local edits are replaced
|       create     written once as a starting point; the project owns it afterwards
*/

return [
    ['from' => 'guidelines', 'to' => '.ai/guidelines/foundation', 'mode' => 'overwrite'],
    ['from' => 'pint.json', 'to' => 'pint.json', 'mode' => 'overwrite'],
    ['from' => 'scripts/laravel.sh', 'to' => '.scripts/laravel.sh', 'mode' => 'overwrite'],
    ['from' => 'phpunit.xml', 'to' => 'phpunit.xml', 'mode' => 'create'],
    ['from' => 'workflows', 'to' => '.github/workflows', 'mode' => 'create'],
];
