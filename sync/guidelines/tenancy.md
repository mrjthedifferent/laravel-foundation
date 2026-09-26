## Tenancy (one database per tenant)

Applies only when `config('foundation.tenancy.enabled')` is true. When it is false, everything in the other guidelines holds as written and this file can be ignored.

### Module context

Every module declares where it runs, in `module.json`:

| `"context"` | Runs | Database | Use for |
|---|---|---|---|
| `universal` (default) | central app and every tenant | whichever is current | foundation modules; anything both staff and tenants use (users, roles, settings) |
| `central` | central domains only | central | platform concerns: tenants, plans, billing, domains |
| `tenant` | inside an initialised tenant only | tenant | the tenant's own business data |

Create a module with its context:

```bash
php artisan foundation:make-module Invoice --context=tenant
```

Choose `central` or `tenant` whenever the module is not needed on both sides. Never change a released module's context without a plan for its existing tables.

### What follows the context automatically

- **Routes.** `routes/web.php` and `routes/api.php` are wrapped with the middleware listed under `foundation.tenancy.middleware.{context}`. Central routes are bound to `foundation.tenancy.central_domains`. Route files stay exactly as in `module-creation.md`.
- **Migrations.**
  - A tenant module's migrations never run in the central database; they run through the tenancy library's tenant migrator (`MigrationPaths::for(ModuleContext::Tenant)`).
  - Universal migrations run in both.
  - `config/modules.php` must have `'auto-discover' => ['migrations' => false]`, and the app refuses to boot otherwise.
- **Sidebar and dashboard.** Menu items, headline stats and charts appear only where their module belongs.
- **Permissions.**
  - `RolePermissionPermissionsSeeder` seeds only the modules that belong where it runs.
  - Add `'contexts' => ['central']` (or `['tenant']`) to a single entry in `config/permissions.php` to restrict that one permission.
  - What a tenant database holds is exactly what its admins can grant.
- **Settings.**
  - Each tenant has its own `settings` table.
  - They are re-applied to `config()` (including the mailer) whenever the tenant changes.
  - The settings cache and the Spatie permission cache are keyed per tenant.

### Rules

- A universal route has **one path** on every host. Do not try to give it a different prefix per context; `foundation.routing.prefix` is global.
- Never query across connections from a model. A central model and a tenant model never share a relation or a transaction. To move data between them, dispatch an event or job that is idempotent (a unique key per event).
- **Jobs.**
  - A job dispatched inside a tenant must run inside that tenant. Rely on the tenancy library's queue integration.
  - Never pass a model from the other side.
- **Schedules** in a tenant module's `routes/console.php` run once, centrally. Fan out per tenant (for example with the library's `tenants:run`, or a job per tenant) instead of querying tenant tables from the central scheduler.
- **Cache keys** the module writes itself go through `Tenancy::cacheKey('...')`, unless the cache store already separates tenants.
- **Tenant awareness.** Check with `Tenancy::enabled()`, `Tenancy::current()` and `Tenancy::context()->inTenant()`. Never call the tenancy library directly from a module; only the project's adapter (`TenancyContext`) does.
- **Super Admin** is central-only. `foundation:super-admin` refuses to run inside a tenant; a tenant's highest privilege is a role.
- **Listening for changes.** To react when the tenant changes, listen to `Mrj\Foundation\Events\TenancyContextChanged` in the module's `$listen`.
