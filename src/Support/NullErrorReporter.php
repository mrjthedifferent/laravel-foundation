<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Mrj\Foundation\Contracts\ErrorReporter;
use Override;
use Throwable;

/**
 * Default binding for ErrorReporter when the ErrorReport module isn't
 * installed. Deliberately does nothing; exceptions still reach the normal
 * exception handler regardless.
 */
final class NullErrorReporter implements ErrorReporter
{
    #[Override]
    public function capture(Throwable $e): void {}
}
