## The Dashboard — Extending It

The dashboard (`admin.dashboard`) is a 12-column **grid of widgets**. Every module adds its own through its service provider; the page itself is never
edited. Each viewer can press **Customize** to drag, resize (3, 4, 6, 8 or 12 of 12 columns), hide and reorder the cards, then **Done** to save; the layout
is stored per user (`dashboard_layouts`) and merged with whatever is registered now, so enabling or disabling a module never corrupts anyone's layout.
The header carries the range picker (7, 14, 30 or 90 days) and a Compare switch (draws the previous period).

A project replaces the whole page by creating `resources/views/dashboard.blade.php`, or turns the route off (`foundation.routing.dashboard => false`).

### What a module can contribute

Declare them on the module's service provider (it extends `Mrj\Foundation\Support\ModuleServiceProvider`). Each one is **permission-gated**, **cached**
through `DashboardCache` (TTL `settings.dashboard_cache_ttl_minutes`, 0 disables), and contributes nothing when the viewer may not see it.

| Property on the provider | Class to extend | Adds |
|---|---|---|
| `$dashboardStats = [XStatComposer::class]` | `Support\StatComposer` | Headline KPI cards (with optional sparkline) |
| `$dashboardCharts = [XChart::class]` | `Support\ChartComposer` | The trend chart (lowest priority the viewer may see wins) |
| `$dashboardWidgets = [XWidget::class]` | `Support\DashboardWidget` | A card on the grid the viewer can move, resize and hide |
| `$dashboardActions = [XActions::class]` | `Support\QuickActionComposer` | Shortcuts in the Quick actions card |
| `$dashboardHealth = [XHealth::class]` | `Support\HealthCheck` | A line in the System health card |
| `$composers = ['x::partials.dashboard-widget' => XWidgetComposer::class]` | `Support\WidgetComposer` | The older card style; still works and joins the grid as `module:x` |

Disabled modules register nothing, so their cards vanish. With tenancy on, only classes whose module belongs to the current context take part.

#### Headline stat

```php
final class InvoiceStatComposer extends StatComposer
{
    public function priority(): int { return 50; }               // lower sorts first
    protected function permissions(): array { return ['View Invoice']; }
    protected function key(): string { return 'invoice'; }       // cached as stat:invoice

    protected function build(): array                            // scalars and arrays only
    {
        return [[
            'label' => __('invoice::invoice.stat.open'),
            'value' => number_format(Invoice::query()->whereNull('paid_at')->count()),
            'icon' => 'ph-receipt',
            'color' => 'warning',                                // primary|success|warning|danger|info|secondary
            'href' => route('admin.invoices.index'),
            'change' => '12%', 'changeUp' => true,               // optional trend pill
            'caption' => __('invoice::invoice.stat.this_month'),
            'series' => array_values(app(DailySeries::class)->count(Invoice::query()->toBase(), 'created_at', 14)),  // optional sparkline
        ]];
    }
}
```

#### Trend chart

```php
final class InvoicesChart extends ChartComposer
{
    public function label(int $days): string { return __('invoice::invoice.chart.title', ['days' => $days]); }
    public function priority(): int { return 20; }
    protected function permissions(): array { return ['View Invoice']; }
    protected function key(): string { return 'invoices'; }
    protected function build(int $days): array { return app(DailySeries::class)->count(Invoice::query()->toBase(), 'created_at', $days); }
    protected function buildPrevious(int $days): ?array   // optional: enables Compare
    {
        return app(DailySeries::class)->count(Invoice::query()->toBase(), 'created_at', $days, $days);   // 4th arg shifts the window back
    }
}
```
`DailySeries::count(query, column, days, shift = 0)` groups by day in SQL on every driver and fills gaps with zeros (max 90 days).

#### A customizable widget

```php
final class OpenInvoicesWidget extends DashboardWidget
{
    public function key(): string { return 'open-invoices'; }     // stored in saved layouts — never change it once released
    public function title(): string { return __('invoice::invoice.widget.open'); }
    public function icon(): string { return 'ph-receipt'; }
    public function width(): int { return 4; }                    // default: 3, 4, 6, 8 or 12 of 12
    public function order(): int { return 50; }                   // default place; lower first
    public function permissions(): array { return ['View Invoice']; }   // any one is enough; [] = everyone

    protected function cacheKey(DashboardContext $context): string { return $this->key().':'.$context->days; }   // include what changes the answer

    protected function data(DashboardContext $context): ?array    // plain data only; null = show nothing (card leaves the page)
    {
        return ['series' => app(DailySeries::class)->count(Invoice::query()->toBase(), 'created_at', $context->days), 'days' => $context->days];
    }

    protected function view(array $data, DashboardContext $context): View { return view('invoice::partials.widget-open', $data); }
}
```
```blade
{{-- Modules/Invoice/resources/views/partials/widget-open.blade.php --}}
<div class="card h-full">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-receipt"></i></span>
        <h2 class="card-title">{{ __('invoice::invoice.widget.open') }}</h2>
    </div>
    <div class="card-body"><x-chart-bar :series="$series" label="…" /></div>
</div>
```
`DashboardContext` has `$days` (7|14|30|90) and `$compare`. The widget's HTML is already rendered when the page is built; hidden widgets are not rendered.
Per-viewer data (not application-wide) must override `load()` to skip the shared cache entry (see `StatsWidget`).

#### Quick actions and health

```php
final class InvoiceQuickActions extends QuickActionComposer
{
    public function priority(): int { return 25; }
    public function actions(): array
    {
        return [['label' => __('invoice::invoice.quick.new'), 'icon' => 'ph-plus', 'href' => route('admin.invoices.create'), 'permission' => 'Create Invoice']];
    }
}

final class InvoiceHealth extends HealthCheck
{
    public function permissions(): array { return ['View Invoice']; }
    public function check(): array   // status: self::OK | self::WARN | self::FAIL; problems list first; a throwing check is shown as failed
    {
        $overdue = Invoice::query()->where('due_at', '<', now())->whereNull('paid_at')->count();
        return ['status' => $overdue ? self::WARN : self::OK, 'label' => __('invoice::invoice.health.label'),
                'detail' => trans_choice('invoice::invoice.health.overdue', $overdue, ['count' => $overdue]), 'href' => route('admin.invoices.index')];
    }
}
```

### Charts and building blocks

`<x-chart-area>`, `<x-chart-bar>`, `<x-chart-donut>`, `<x-chart-heatmap>`, `<x-sparkline>`, `<x-stat-card>`, `<x-empty-state>` — see `components-reference.md`.
`HourlySeries::matrix(query, column, days)` returns the weekday×hour counts for the heatmap. All charts are SVG/HTML on the theme tokens; **do not add a
charting library**.

### Rules

- Cached payloads are scalars and arrays only (Laravel 13 apps refuse objects from shared cache stores).
- Always gate with permissions; never show data the viewer could not open in its module.
- Never reuse or rename a widget `key()`; saved layouts refer to it.
- A card is `<div class="card h-full">` with a `.card-header` (icon tile, `h2.card-title`, optional link) and a `.card-body`; do not repeat what a headline stat already shows.
- Endpoints: `PUT admin/dashboard/layout` (`items: [{key, width, hidden}]`) and `DELETE admin/dashboard/layout` (reset) act on the signed-in user only.
