<?php

declare(strict_types=1);

namespace Modules\ErrorReport\Actions;

use Modules\ErrorReport\Models\ErrorReport;

final readonly class ResolveErrorReportAction
{
    public function execute(ErrorReport $errorReport): void
    {
        $errorReport->resolve();
    }
}
