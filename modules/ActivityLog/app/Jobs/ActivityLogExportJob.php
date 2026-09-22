<?php

namespace Modules\ActivityLog\Jobs;

use BackedEnum;
use Modules\ActivityLog\Helpers\ActivityLogHelper;
use Modules\ActivityLog\Queries\ActivityLogQuery;
use Mrj\Foundation\Support\ExportJob;
use Override;
use OwenIt\Auditing\Models\Audit;

class ActivityLogExportJob extends ExportJob
{
    #[Override]
    protected function buildData(): array
    {
        $filters = $this->filters;
        $exportData = [];
        $sl = 1;

        ActivityLogQuery::make()
            ->withUser()
            ->search($filters['search'] ?? null)
            ->filterByEvent($filters['event'] ?? null)
            ->filterByAuditableType($filters['auditable_type'] ?? null)
            ->filterByUserId($filters['user_id'] ?? null)
            ->filterByDateFrom($filters['date_from'] ?? null)
            ->filterByDateTo($filters['date_to'] ?? null)
            ->get()
            ->each(function (Audit $audit) use (&$exportData, &$sl): void {
                $metaData = $audit->getMetadata();
                $modifiedData = $audit->getModified();
                $changes = [];

                foreach ($modifiedData as $key => $value) {
                    if (is_array($value) && isset($value['old'], $value['new'])) {
                        $old = self::castToString($value['old']);
                        $new = self::castToString($value['new']);
                        $changes[] = ActivityLogHelper::titleCase($key).': '.$old.' → '.$new;
                    } else {
                        $changes[] = ActivityLogHelper::titleCase($key).': '.self::castToString($value);
                    }
                }

                $exportData[] = [
                    'SL' => $sl++,
                    'ID' => $audit->id,
                    'Event' => ucfirst($audit->event),
                    'Entity Type' => ActivityLogHelper::getModelName($audit->auditable_type),
                    'Entity ID' => $audit->auditable_id,
                    'Changes' => implode(', ', $changes),
                    'IP Address' => $metaData['audit_ip_address'] ?? 'N/A',
                    'URL' => $metaData['audit_url'] ?? 'N/A',
                    'User Agent' => $metaData['audit_user_agent'] ?? 'N/A',
                    'User' => $audit->user
                        ? "{$audit->user->name} (ID: {$audit->user->getKey()})"
                        : 'System',
                    'Date' => $audit->created_at->format(config('foundation.formats.datetime')),
                ];
            });

        return $exportData;
    }

    #[Override]
    protected function title(): string
    {
        return 'Activity Logs';
    }

    #[Override]
    protected function filenamePrefix(): string
    {
        return 'activity_logs';
    }

    private static function castToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        // Backed enum (e.g. ImportStatus, ImportType, Gender, etc.)
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        // Unit enum or any object with __toString
        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : get_class($value);
        }

        return (string) $value;
    }
}
