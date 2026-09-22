<?php

declare(strict_types=1);

namespace Modules\Notification\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Mrj\Foundation\Contracts\SmsGateway;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        protected string $message,
        protected string $phone,
    ) {}

    public function handle(SmsGateway $gateway): void
    {
        $gateway->send($this->phone, $this->message);
    }
}
