<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;
use RuntimeException;

final readonly class DeleteActivityLogAction
{
    /**
     * @throws RuntimeException
     */
    public function execute(int $id): void
    {
        try {
            Audit::findOrFail($id)->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete activity log: '.$e->getMessage());

            throw new RuntimeException('Failed to delete activity log.', 0, $e);
        }
    }
}
