<?php

declare(strict_types=1);

namespace Modules\Settings\Events;

/**
 * Fired whenever a Setting is saved or deleted. RestartQueueWorkers listens
 * for it to signal long-running queue workers to pick up the change.
 */
final class SettingsUpdated {}
