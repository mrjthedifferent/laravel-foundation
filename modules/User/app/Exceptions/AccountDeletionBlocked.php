<?php

declare(strict_types=1);

namespace Modules\User\Exceptions;

use RuntimeException;

/**
 * An account can't be deleted yet; each message says what to settle first.
 */
final class AccountDeletionBlocked extends RuntimeException
{
    /**
     * @param  list<string>  $blockers
     */
    public function __construct(public readonly array $blockers)
    {
        parent::__construct(implode(' ', $blockers));
    }
}
