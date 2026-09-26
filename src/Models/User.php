<?php

namespace Mrj\Foundation\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Modules\ActivityLog\Models\Device;
use Modules\Notification\Models\FirebaseToken;
use Modules\User\Enum\Gender;
use Modules\User\Models\UserDocument;
use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Support\Email;
use Mrj\Foundation\Support\PhoneNumber;
use Mrj\Foundation\Traits\HasImageAttribute;
use Override;
use OwenIt\Auditing\Auditable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Base user every project extends as App\Models\User. Foundation code always
 * refers to App\Models\User, so project-specific relations and rules added on
 * the subclass are available everywhere.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $phone E.164, e.g. +8801712345678
 * @property Carbon|null $phone_verified_at
 * @property string|null $name
 * @property string $password
 * @property bool $must_change_password
 * @property bool $is_active
 * @property bool $is_super_admin
 * @property string|null $two_factor_secret
 * @property list<string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $image
 * @property string|null $gender
 * @property string|null $provider
 * @property string|null $provider_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations registered dynamically via resolveRelationUsing() by the module
 * that owns each related model — see each module's ServiceProvider::boot().
 * @property-read Collection<int, FirebaseToken> $firebaseTokens
 * @property-read Collection<int, Device> $devices
 * @property-read Collection<int, UserDocument> $documents
 * @property-read Collection<int, UserLoginHistory> $loginHistory
 * @property-read UserLoginHistory|null $latestLogin
 *
 * @method HasMany firebaseTokens()
 * @method HasMany devices()
 * @method HasMany documents()
 * @method HasMany loginHistory()
 * @method HasOne latestLogin()
 *
 * @api
 */
abstract class User extends Authenticatable implements \OwenIt\Auditing\Contracts\Auditable, MustVerifyEmail
{
    use Auditable, HasApiTokens, HasFactory, HasImageAttribute, HasRoles, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'phone',
        'password',
        'is_active',
        'provider',
        'provider_id',
        'name',
        'image',
        'gender',
        'email_verified_at',
    ];

    /**
     * Image fields cleaned up (their stored file deleted) by HasImageAttribute
     * when the model is hard-deleted.
     */
    protected array $imageFields = ['image'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Store the login email in canonical form (lowercase, trimmed, invisible
     * characters stripped) so Postgres' case-sensitive lookups always match.
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn ($value) => Email::normalize($value));
    }

    /**
     * Store the phone number in E.164 form (+8801712345678) however it was typed,
     * so sign-in, uniqueness and SMS delivery all compare one form.
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn ($value) => PhoneNumber::toE164($value === null ? null : (string) $value));
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->uuid = (string) Str::uuid();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            // Deliberately not in $fillable: no form or API request can set it.
            'is_super_admin' => 'boolean',
            // Unlike the relations above, a cast has no resolveRelationUsing()
            // equivalent for a module to register from outside; the User module
            // is not optional the way Otp/ErrorReport are, so this one import
            // is kept rather than built a lifecycle-event workaround for it.
            'gender' => Gender::class,
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Two-factor secrets never reach the audit trail, whatever the project's
     * $auditExclude or audit.exclude says.
     *
     * @return array<int, string>
     */
    public function getAuditExclude(): array
    {
        return array_values(array_unique([
            ...($this->auditExclude ?? config('audit.exclude', [])),
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]));
    }

    /**
     * Two-factor authentication is on: a secret exists and the user has
     * confirmed it with a code from their authenticator.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Must this user turn on two-factor authentication before using the panel?
     * By default: foundation.two_factor.required_roles, and super admins when
     * foundation.two_factor.required_for_super_admins is on. Projects override
     * this for their own rules (a per-tenant setting, for example).
     */
    public function requiresTwoFactor(): bool
    {
        if (! config('foundation.two_factor.enabled')) {
            return false;
        }

        if ($this->isSuperAdmin() && config('foundation.two_factor.required_for_super_admins')) {
            return true;
        }

        $roles = (array) config('foundation.two_factor.required_roles', []);

        return $roles !== [] && $this->hasAnyRole($roles);
    }

    protected function image(): Attribute
    {
        return $this->imageAttribute(column: 'image', defaultImage: 'images/person.png', directory: 'images/users');
    }

    /**
     * A Super Admin passes every permission check (see FoundationServiceProvider's
     * Gate::before) whatever its roles. The flag is not a role, so nothing in the
     * admin UI can grant, strip or edit it: only `php artisan foundation:super-admin`.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Project hook: a reason this otherwise-active account must be locked out
     * (e.g. a linked record ended), or null to allow access. Checked on every
     * request by CheckUserIsActive.
     */
    public function accessDenialMessage(): ?string
    {
        return null;
    }

    /**
     * Find users by phone number. Any typed form is accepted; a value that is
     * not a phone number matches nobody. A project that keeps phone numbers
     * somewhere else overrides this scope.
     */
    public function scopeWherePhone(Builder $query, ?string $phone): Builder
    {
        $phone = PhoneNumber::toE164($phone);

        return $phone === null ? $query->whereRaw('1 = 0') : $query->where($this->qualifyColumn('phone'), $phone);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Route notifications for the FCM channel.
     *
     * Tokens may be registered through either the device-token endpoint
     * (device_tokens) or the firebase-token endpoint (firebase_tokens). Both
     * stores are read so push delivery does not silently fail when a client
     * used one endpoint but the channel read only the other.
     *
     * @return array<int, string>
     */
    public function routeNotificationForFcm(): array
    {
        return $this->firebaseTokens()->pluck('token')
            ->merge($this->deviceTokens()->pluck('token'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Remove every push token so a locked-out account stops receiving pushes.
     */
    public function revokePushTokens(): void
    {
        $this->firebaseTokens()->delete();
        $this->deviceTokens()->delete();
    }
}
