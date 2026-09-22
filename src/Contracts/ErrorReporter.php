<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

use Throwable;

/**
 * Always bound (see FoundationServiceProvider), so callers never need a
 * class_exists()/bound() check — Foundation::exceptions() calls this
 * unconditionally. The ErrorReport module rebinds it to a real
 * implementation when installed; otherwise the default no-op applies.
 */
interface ErrorReporter
{
    public function capture(Throwable $e): void;
}
