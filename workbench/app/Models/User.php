<?php

namespace App\Models;

use Mrj\Foundation\Models\User as FoundationUser;

/**
 * Stands in for a project's user model while testing the package.
 */
class User extends FoundationUser
{
    /**
     * Ids locked out by "the project's own rule" (see accessDenialMessage()).
     *
     * @var list<int>
     */
    public static array $lockedOut = [];

    public function accessDenialMessage(): ?string
    {
        return in_array($this->getKey(), self::$lockedOut, true) ? 'Access has ended.' : null;
    }
}
