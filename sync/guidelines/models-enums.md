## Models

```php
class Thing extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable;
    use HasImageAttribute; // include if the model has image/file fields

    protected array $imageFields       = ['image'];
    protected array $imageDirectories  = ['image' => 'images/things'];
    protected array $imageDefaults     = ['image' => 'images/placeholder.png'];

    protected $fillable = ['name', 'status', 'user_id', 'is_active', 'image'];

    public function casts(): array
    {
        return [
            'status'    => ThingStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
```

- Implement `OwenIt\Auditing\Contracts\Auditable` and use the `Auditable` trait on every model that should be change-tracked.
- Use the `casts()` method (not `$casts` property) for all casts.
- All relationship methods must have explicit return type hints.
- Use `HasImageAttribute` trait for models with image/file fields. Always configure all three properties:
  - `$imageFields` — list of column names that hold image paths.
  - `$imageDirectories` — storage directory per field (e.g. `'image' => 'images/things'`).
  - `$imageDefaults` — fallback image path per field shown when the column is null (e.g. `'image' => 'images/placeholder.png'`).
- Use `auditAttach()` / `auditSync()` instead of `attach()` / `sync()` on auditable pivot relations.
- Use query scopes for commonly filtered states: `scopeActive`, `scopeEnabled`.

---

## Enums

All module-specific enums live in `Modules/{Name}/app/Enum/`. Cross-module enums live in `app/Enum/`.

Every enum must implement these three methods:

```php
enum ThingStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active   => __('Active'),
            self::Inactive => __('Inactive'),
            self::Pending  => __('Pending'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
```

- Always backed string enums (`enum Foo: string`).
- Case names are TitleCase: `Active`, `InProgress`, `SoftDeleted`.
- Case values are snake_case strings: `'active'`, `'in_progress'`, `'soft_deleted'`.
- Use `SomeEnum::values()` in validation rules: `Rule::in(ThingStatus::values())`.
- Use `SomeEnum::options()` in Blade select dropdowns.
- Cast enum columns in models via the `casts()` method.
