<?php

declare(strict_types=1);

namespace Modules\User\Console;

use Illuminate\Console\Command;
use Modules\User\Services\AccountDeletion;

/**
 * Daily: anonymizes accounts whose deletion date has come (unless something now blocks it),
 * then drops the security logs of anonymized accounts older than security_log_days.
 */
final class PurgeDeletedAccounts extends Command
{
    protected $signature = 'accounts:purge-deleted';

    protected $description = 'Delete (anonymize) accounts whose deletion request is due, and prune their old security logs';

    public function handle(AccountDeletion $deletion): int
    {
        $result = $deletion->runDue();
        $pruned = $deletion->pruneSecurityLogs();

        $this->info(__('user::user.deletion.purged', [...$result, 'pruned' => $pruned]));

        return self::SUCCESS;
    }
}
