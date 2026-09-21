<?php

declare(strict_types=1);

namespace Modules\ErrorReport\Actions;

use Modules\ErrorReport\Models\ErrorReport;

final readonly class DeleteErrorReportAction
{
    public function execute(ErrorReport $errorReport): void
    {
        $errorReport->delete();
    }
}
