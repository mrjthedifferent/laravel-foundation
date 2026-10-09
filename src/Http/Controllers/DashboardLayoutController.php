<?php

namespace Mrj\Foundation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mrj\Foundation\Services\Dashboard\DashboardLayoutService;
use Mrj\Foundation\Support\DashboardWidget;

/**
 * Saves and resets the signed-in viewer's own arrangement of the dashboard. It only ever
 * touches the viewer's own row, so no permission beyond being signed in is involved.
 *
 * @internal
 */
final class DashboardLayoutController extends Controller
{
    public function update(Request $request, DashboardLayoutService $layouts): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'max:100'],
            'items.*.key' => ['required', 'string', 'max:100'],
            'items.*.width' => ['nullable', 'integer', 'in:'.implode(',', DashboardWidget::WIDTHS)],
            'items.*.hidden' => ['nullable', 'boolean'],
        ]);

        return response()->json(['layout' => $layouts->save($request->user(), $validated['items'])]);
    }

    public function destroy(Request $request, DashboardLayoutService $layouts): JsonResponse
    {
        $layouts->reset($request->user());

        return response()->json(['layout' => $layouts->resolve($request->user())]);
    }
}
