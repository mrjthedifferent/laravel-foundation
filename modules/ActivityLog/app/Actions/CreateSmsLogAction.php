<?php

namespace Modules\ActivityLog\Actions;

use Modules\ActivityLog\Models\SmsLog;

final readonly class CreateSmsLogAction
{
    public function execute(string $phone, string $message, mixed $result): SmsLog
    {
        return SmsLog::create([
            'status' => $result === false ? 'failed' : 'success',
            'phone' => $phone,
            'message' => $message,
            'response' => json_encode($result, JSON_THROW_ON_ERROR),
        ]);
    }
}
