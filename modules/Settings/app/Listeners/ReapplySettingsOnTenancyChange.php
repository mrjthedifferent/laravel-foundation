<?php

declare(strict_types=1);

namespace Modules\Settings\Listeners;

use Illuminate\Mail\MailManager;
use Modules\Settings\Support\SettingsConfigApplier;

/**
 * The tenant changed: its settings replace the previous context's in config().
 * Resolved mailers are dropped too, since each was built from the old mail.* values.
 */
final readonly class ReapplySettingsOnTenancyChange
{
    public function __construct(
        private SettingsConfigApplier $applier,
        private MailManager $mail,
    ) {}

    public function handle(): void
    {
        $this->applier->apply();
        $this->mail->forgetMailers();
    }
}
