<?php

declare(strict_types=1);

namespace Modules\User\Enum;

/**
 * Where an account deletion request stands.
 */
enum DeletionStatus: string
{
    /** Waiting for staff (automatic deletion is off). */
    case PendingReview = 'pending_review';
    /** Will be anonymized at scheduled_for, unless the person signs in first. */
    case Scheduled = 'scheduled';
    case Rejected = 'rejected';
    /** The person signed in again, or asked to keep the account. */
    case Cancelled = 'cancelled';
    /** Anonymized. */
    case Done = 'done';

    /** Still waiting to happen: blocks sign-in until cancelled. */
    public function isOpen(): bool
    {
        return $this === self::PendingReview || $this === self::Scheduled;
    }

    public function label(): string
    {
        return __("user::user.deletion.status.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function open(): array
    {
        return [self::PendingReview->value, self::Scheduled->value];
    }
}
