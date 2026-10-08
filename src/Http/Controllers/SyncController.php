<?php

declare(strict_types=1);

namespace Mrj\Foundation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Mrj\Foundation\Sync\PullSyncChanges;
use Mrj\Foundation\Sync\PushSyncChanges;
use Mrj\Foundation\Sync\SyncOperation;
use Mrj\Foundation\Sync\SyncRegistry;
use Mrj\Foundation\Sync\SyncResult;

/**
 * Offline sync endpoints. Registered only when `foundation.offline_sync.handlers`
 * lists at least one collection.
 */
class SyncController extends Controller
{
    public function pull(Request $request, PullSyncChanges $pull): JsonResponse
    {
        $max = (int) config('foundation.offline_sync.max_pull_limit', 1000);
        $data = $request->validate([
            'cursor' => ['nullable', 'string', 'max:8192'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$max],
            'only' => ['nullable', 'string'],
        ]);

        $only = isset($data['only']) ? array_values(array_filter(explode(',', $data['only']))) : null;

        return JsonResponseFactory::success(
            __('foundation::foundation.offline_sync.pulled'),
            $pull->execute(
                $request->user(),
                $data['cursor'] ?? null,
                $only,
                (int) ($data['limit'] ?? config('foundation.offline_sync.pull_limit', 500)),
            ),
        );
    }

    public function push(Request $request, PushSyncChanges $push): JsonResponse
    {
        $data = $request->validate([
            'ops' => ['required', 'array', 'min:1', 'max:'.(int) config('foundation.offline_sync.max_push_ops', 200)],
            'ops.*.name' => ['required', 'string', Rule::in(SyncRegistry::names())],
            'ops.*.op' => ['required', Rule::in([SyncOperation::UPSERT, SyncOperation::DELETE])],
            'ops.*.id' => ['required', 'string', 'max:64'],
            'ops.*.version' => ['nullable', 'integer', 'min:0'],
            'ops.*.data' => ['nullable', 'array'],
        ]);

        $results = $push->execute(
            $request->user(),
            array_map(SyncOperation::fromArray(...), $data['ops']),
        );

        return JsonResponseFactory::success(
            __('foundation::foundation.offline_sync.pushed'),
            ['results' => array_map(fn (SyncResult $r): array => $r->toArray(), $results)],
        );
    }
}
