<?php

declare(strict_types=1);

namespace Modules\Notification\Support;

use Closure;

/**
 * Carries the text that should be recorded in SMS logs in place of the real
 * message while a send is in progress, so secrets such as one-time codes
 * are delivered to the phone but never stored in `sms_logs` or log files.
 *
 * A notification opts in by defining `toSmsLog(object $notifiable): string`.
 */
final class SmsLogRedaction
{
    private static ?string $logMessage = null;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function during(?string $logMessage, Closure $callback): mixed
    {
        $previous = self::$logMessage;
        self::$logMessage = $logMessage;

        try {
            return $callback();
        } finally {
            self::$logMessage = $previous;
        }
    }

    /**
     * The text to log for a message being sent: the redacted version when
     * one was supplied, otherwise the message itself.
     */
    public static function forLog(string $message): string
    {
        return self::$logMessage ?? $message;
    }
}
