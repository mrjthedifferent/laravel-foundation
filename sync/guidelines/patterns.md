## Architecture Patterns

### Controllers — Ultra-Thin

Controllers must do exactly: **authorize → delegate to Action → respond**. No business logic, DB queries, or validation rules inside a controller.

```php
class ThingController extends Controller
{
    public function store(StoreThingRequest $request, CreateThingAction $action): RedirectResponse
    {
        $this->authorize('create', Thing::class);

        $action->execute(ThingData::from($request->validated()));

        return redirect()->route('admin.things.index')
            ->with('success', 'Thing created successfully');
    }
}
```

- Authorization always via `$this->authorize()` calling a policy method; never inline `if ($user->cannot(...))` checks.
- Actions are injected as method parameters (resolved by the service container).
- **Do not wrap an Action call in try/catch to flash a generic error.** Let the
  exception propagate — `Mrj\Foundation\Exceptions\Handler` already renders a
  friendly fallback for any web request's unhandled server error in
  production (and the real error in every other environment, for debugging),
  and swallowing the exception here means it never reaches that handler, the
  logs, or `ErrorReporter`. A try/catch in a controller is still correct when
  it does something a generic handler can't: an external service call
  (OAuth, a "test connection" endpoint) whose failure needs a
  specific, actionable message back to the user.

---

### Actions — Business Logic

Actions encapsulate one unit of domain work with a single public `execute()` method. Only inject a Service when it is genuinely needed — most simple actions need no dependencies at all.

```php
final readonly class CreateThingAction
{
    public function execute(ThingData $data): Thing
    {
        return DB::transaction(function () use ($data) {
            $thing = Thing::create([
                'name'      => $data->name,
                'is_active' => $data->is_active,
            ]);

            event(new ThingCreated($thing));

            return $thing->fresh(['relations']);
        });
    }
}
```

- Always `final readonly`.
- Use `DB::transaction()` for any operation touching multiple tables.
- Dispatch domain events from inside the action, not the controller.
- Return typed values — never `void` when a model is created.
- Use `auditAttach()` / `auditSync()` instead of raw `attach()` / `sync()` for pivot relations (OwenIt Auditing).

---

### Services — Only When Logic Is Shared Across Multiple Actions

Do **not** create a Service class just because an action exists. A Service is only justified when the same logic is needed in two or more actions.

**Real example:** `UserRoleService` is injected into both `CreateUserAction` and `UpdateUserAction` because both need to resolve Role models from IDs.

```php
final readonly class ThingCategoryService
{
    /**
     * @param  array<int, int> $ids
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    public function getModels(array $ids): \Illuminate\Database\Eloquent\Collection
    {
        return Category::whereIn('id', $ids)->get();
    }
}

// Injected into CreateThingAction AND UpdateThingAction — justified.
final readonly class CreateThingAction
{
    public function __construct(
        private ThingCategoryService $categoryService,
    ) {}
    ...
}
```

- Always `final readonly` with constructor injection only.
- If only one action needs the logic, put it directly in that action — no service needed.

---

### Query Builders — Fluent Filtering

```php
final readonly class ThingQuery
{
    public function __construct(private Builder $query) {}

    public static function make(): self
    {
        return new self(Thing::query());
    }

    public function withRelations(array $relations = []): self
    {
        return new self($this->query->with($relations));
    }

    public function filterByStatus(?bool $isActive): self
    {
        if ($isActive === null) {
            return $this;
        }

        return new self($this->query->where('is_active', $isActive));
    }

    public function search(?string $search): self
    {
        if (empty($search)) {
            return $this;
        }

        return new self(
            $this->query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
        );
    }

    public function orderByLatest(): self
    {
        return new self($this->query->orderBy('id', 'desc'));
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    public function get(): Collection
    {
        return $this->query->get();
    }
}
```

Usage in controllers:
```php
$things = ThingQuery::make()
    ->withRelations(['category'])
    ->filterByStatus($request->boolean('is_active'))
    ->search($request->input('search'))
    ->orderByLatest()
    ->paginate(PaginationEnum::DEFAULT_LIST);
```

- Always `final readonly`.
- Each filter returns `new self(...)` — never mutates `$this->query`.
- Null/empty guard at the top of every filter (`if ($value === null) { return $this; }`).
- Always call `->withQueryString()` on paginated results.

---

### DTOs — Validation Rule Repositories

DTOs (Spatie `LaravelData`) are the single source of truth for all validation rules.

```php
class ThingData extends Data
{
    public function __construct(
        public string $name,
        public bool $is_active = true,
        public ?array $tag_ids = [],
    ) {}

    public static function createRules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'tag_ids'   => ['nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
        ];
    }

    public static function updateRules(?int $id = null): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'tag_ids'   => ['nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'name.required' => 'The name field is required.',
        ];
    }
}
```

Form Requests delegate entirely to the DTO:
```php
class StoreThingRequest extends FormRequest
{
    public function authorize(): bool { return true; } // policy handles this in the controller

    public function rules(): array { return ThingData::createRules(); }

    public function messages(): array { return ThingData::messages(); }
}
```

In controllers: `ThingData::from($request->validated())`.

---

### Policies — Authorization

Policies are the **only** place where permission checks are expressed. They delegate to Spatie's `$user->can('Permission Name')` — never use `$user->hasRole()` or raw Gate checks in application code.

```php
class ThingPolicy
{
    public function viewAny(User $user): bool { return $user->can('View Thing'); }
    public function view(User $user, Thing $thing): bool { return $user->can('View Thing') || $user->id === $thing->user_id; }
    public function create(User $user): bool { return $user->can('Create Thing'); }
    public function update(User $user, Thing $thing): bool { return $user->can('Edit Thing') || $user->id === $thing->user_id; }

    public function delete(User $user, Thing $thing): bool
    {
        if ($user->id === $thing->user_id) {
            return false; // business rule: self-delete not allowed
        }

        return $user->can('Delete Thing');
    }
}
```

Register in `{Name}ServiceProvider::boot()`:
```php
Gate::policy(Thing::class, ThingPolicy::class);
```

- Policy method names match controller action names: `viewAny`, `view`, `create`, `update`, `delete`.
- Permission strings used inside policies **must** exist in the module's `config/permissions.php`.
- Self-action guards (e.g. "cannot delete yourself") go inside the policy, not the controller.
- Blade directives `@can` / `@cannot` / `@canany` automatically resolve through these policies via Spatie's `HasPermissions` trait on the `User` model.
- Never use `$user->hasRole()` for authorization logic — always use named permissions so access is role-agnostic.
