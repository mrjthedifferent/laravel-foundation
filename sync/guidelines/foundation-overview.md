## Laravel Foundation — Start Here

`mrjthedifferent/laravel-foundation` is the shared base of every admin application built from it: sign-in, users, roles and permissions,
settings, notifications, activity logs, backups, import/export jobs, a dashboard and a complete admin UI. A project **requires** it and never
copies its files, so a fix made in the package reaches every project with `composer update`.

**Before writing any UI or module code, reuse what the package already ships.** Almost everything a page needs exists as a Blade
component, a CSS class, a PHP base class or a registry. Building it again by hand drifts from the rest of the application and is
overwritten or broken by the next package update.

### Where to look

| You are about to… | Read |
|---|---|
| Build or change a page, form, table, filter, modal, card, badge, button | [`ui-components.md`](ui-components.md) (conventions) and [`components-reference.md`](components-reference.md) (every component, its props and slots) |
| Write page templates (index, create, edit, show) | [`views.md`](views.md) |
| Add JavaScript, a modal, a dropdown, a tab, a tooltip, a toast, a confirmation | [`frontend-js.md`](frontend-js.md) |
| Add a dashboard stat, chart, card, shortcut or health check | [`dashboard.md`](dashboard.md) |
| Change colours, spacing, corners, density, page width, dark mode, RTL | [`theming.md`](theming.md) |
| Create a module, model, controller, action | [`module-creation.md`](module-creation.md), [`module-architecture.md`](module-architecture.md), [`models-enums.md`](models-enums.md), [`patterns.md`](patterns.md) |
| Add permissions, settings, menu entries | [`permissions-settings.md`](permissions-settings.md) |
| Write tests | [`testing.md`](testing.md) |
| Support tenancy | [`tenancy.md`](tenancy.md) |

### The ten rules that matter most

1. **Pages never write shell markup.** One layout, `<x-app-layout>` (a module uses `<x-module-layout>`); a page fills the content slot.
2. **Use the components, not their markup.** `<x-page-header>`, `<x-search-card>`, `<x-table-view-pagination>`, `<x-form.input>`, `<x-form.select>`,
   `<x-form.file>`, `<x-modal>`, `<x-form-section>`, `<x-status-badge>`, `<x-empty-state>`, `<x-stat-card>`. If one fits, use it.
3. **Icons are Phosphor 2 only, written as a weight class plus the icon: `ph ph-gear`** (`ph-bold`/`ph-fill` for other weights). A bare `ph-gear` renders nothing. No Font Awesome.
4. **Layout is Tailwind utilities; look is the component classes.** `flex`, `grid grid-cols-12 gap-4`, `col-span-12 md:col-span-6`, `mb-4` for layout; `.btn`,
   `.card`, `.badge`, `.table`, `.form-control`, `.alert` and the `.fd-*` classes for appearance. Never inline `style=`, never a hard-coded colour.
5. **Never edit package files in a project.** `assets/css/foundation.css`, `assets/js/foundation.js`, anything under `vendor/` is overwritten on update.
   Project CSS goes in the project's own `resources/css/app.css` or an `@push('styles')`.
6. **Behaviour is declarative.** Modals, dropdowns, tabs, collapses, tooltips and confirmations are `data-fd-*` attributes and `swal-*` classes — see
   `frontend-js.md`. There is no Bootstrap: `data-bs-*` and `window.bootstrap` do not exist.
7. **Flash, don't alert.** Controllers return `->with('success', …)`; the layout shows it. JavaScript uses `toast(type, title, text)`.
8. **Authorize and gate.** `$this->authorize()` in every action, `@can('Permission')` in views; widgets, stats, shortcuts and health checks carry the
   permission that gates them.
9. **Cached data is plain data.** Anything the dashboard caches is scalars and arrays — never models or collections.
10. **Translate everything.** `__('module::file.key')`; each key written out in full in the source so it can be found.

### How a project is laid out

```
app/                       the project's own code (thin)
Modules/<Name>/            the project's modules (see module-creation.md)
resources/css/app.css      project CSS: imports the foundation's, then adds the project's own
resources/views/           overrides: dashboard.blade.php, pagination/, layouts/ … only when the project must differ
public/assets/             the package's compiled assets (published, git-ignored)
.ai/guidelines/foundation/ these guideline files (synced by `php artisan foundation:sync`)
```

Package modules (User, RolePermission, Settings, Notification, ActivityLog, BackupCleanup, ErrorReport, Otp, ImportDownloadManager) are
enabled or disabled per project; everything they contribute — menu entries, permissions, dashboard widgets — appears and disappears with them.

### Commands

| Command | Does |
|---|---|
| `php artisan foundation:install` | Sets a project up to use the package (safe to run again) |
| `php artisan foundation:publish [--force] [--link]` | Copies (or links) the compiled theme assets to `public/assets` |
| `php artisan foundation:sync` | Refreshes these guidelines, `pint.json`, the deploy script and workflow templates |
| `php artisan foundation:make-module Name` | Creates a module that follows the conventions |
| `php artisan foundation:super-admin email` | Creates or promotes a Super Admin |

Run `foundation:publish --force` and `foundation:sync` after every `composer update` of the package (the install adds both to Composer's
post-update hooks).

### Upgrading from 1.x (Bootstrap) to 2.x (Tailwind)

The UI moved from Bootstrap 5 to Tailwind CSS 4 with the same look. Views that only use the package's components need no change. Views that wrote
Bootstrap classes or `data-bs-*` attributes themselves: run
`node vendor/mrjthedifferent/laravel-foundation/bin/migrate-bootstrap-to-tailwind.mjs resources/views --write` **once** on a clean git tree and review the
diff (spacing is renumbered: Bootstrap `mb-3` is Tailwind `mb-4`). Then run `php artisan migrate` (adds `dashboard_layouts`).

### Upgrading from 2.x to 3.x (Phosphor 2, jQuery 4)

The bundled libraries moved to their latest releases. Two of them change a project's code:

- **Phosphor 2 icons need a weight class:** `ph-gear` is now `ph ph-gear`, and the 1.x suffix `ph-star-fill` is now `ph-fill ph-star`. A bare `ph-gear` renders nothing. Run
  `node vendor/mrjthedifferent/laravel-foundation/bin/migrate-phosphor-2.mjs Modules resources config app --write` and review the diff. It rewrites class attributes,
  `icon` props and `'icon' => …` values (menus, `config/sidebar.php`, widgets), and is safe to run again. Every Phosphor 1 icon name still exists in 2.x.
- **jQuery 4** removed long-deprecated helpers (`$.trim`, `$.isArray`, `$.isFunction`, `$.parseJSON`, `$.type`, `$.now`…). Replace them in a project's own scripts
  with plain JavaScript (`str.trim()`, `Array.isArray()`, `typeof f === 'function'`, `JSON.parse()`, `Date.now()`).

Then run `php artisan foundation:publish --force`.
