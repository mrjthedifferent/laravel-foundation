<?php

namespace Mrj\Foundation\Services\Dashboard;

use Illuminate\Http\Request;
use Mrj\Foundation\Support\DashboardWidget;

/**
 * Everything the dashboard view draws, in one place: the range the viewer chose, and the
 * widgets in the order and widths they arranged, each already rendered.
 *
 * @internal
 */
final readonly class DashboardPage
{
    public function __construct(
        private WidgetRegistry $registry,
        private DashboardLayoutService $layouts,
    ) {}

    /**
     * Hidden widgets are listed without being rendered, so the layout editor can offer
     * to bring them back. A widget with nothing to show (no data the viewer may see) is
     * left out altogether.
     *
     * @return array{context: DashboardContext, items: list<array{key: string, title: string, icon: string, width: int, hidden: bool, html: string}>}
     */
    public function build(Request $request): array
    {
        $context = DashboardContext::fromRequest($request);
        $user = $request->user();

        if ($user === null) {
            return ['context' => $context, 'items' => []];
        }

        $widgets = $this->registry->all();
        $items = [];

        foreach ($this->layouts->resolve($user) as $slot) {
            /** @var DashboardWidget $widget */
            $widget = $widgets[$slot['key']];
            $html = $slot['hidden'] ? '' : $widget->render($context);

            if (! $slot['hidden'] && $html === '') {
                continue;
            }

            $items[] = [
                'key' => $slot['key'],
                'title' => $widget->title(),
                'icon' => $widget->icon(),
                'width' => $slot['width'],
                'hidden' => $slot['hidden'],
                'html' => $html,
            ];
        }

        return ['context' => $context, 'items' => $items];
    }
}
