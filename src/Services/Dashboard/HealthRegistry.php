<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Support\HealthCheck;
use Throwable;

/**
 * The system health checks the enabled modules offer. A check that throws is reported as
 * failed rather than breaking the dashboard.
 *
 * @internal
 */
final class HealthRegistry
{
    /** @use RegistersComposers<HealthCheck> */
    use RegistersComposers;

    private const array RANK = [HealthCheck::FAIL => 0, HealthCheck::WARN => 1, HealthCheck::OK => 2];

    public function __construct(private readonly DashboardCache $cache) {}

    /**
     * What is wrong first, then what is fine, for the checks the viewer may see.
     *
     * @return list<array{status: string, label: string, detail?: string, href?: string}>
     */
    public function all(): array
    {
        $user = auth()->user();
        $results = [];

        foreach ($this->instances() as $check) {
            if (! $user?->canAny($check->permissions())) {
                continue;
            }

            $results[] = ['priority' => $check->priority()] + $this->run($check);
        }

        usort($results, fn (array $a, array $b): int => [self::RANK[$a['status']] ?? 3, $a['priority']] <=> [self::RANK[$b['status']] ?? 3, $b['priority']]);

        return array_map(function (array $result): array {
            unset($result['priority']);

            return $result;
        }, $results);
    }

    /**
     * @return array{status: string, label: string, detail?: string, href?: string}
     */
    private function run(HealthCheck $check): array
    {
        try {
            return $this->cache->remember('health:'.$check::class, $check->check(...));
        } catch (Throwable $exception) {
            report($exception);

            return [
                'status' => HealthCheck::FAIL,
                'label' => class_basename($check),
                'detail' => __('foundation::foundation.dashboard.health_check_failed'),
            ];
        }
    }
}
