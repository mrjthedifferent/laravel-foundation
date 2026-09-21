<?php

namespace Mrj\Foundation\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property bool $is_active
 * @property string|null $image
 * @property string|null $gender
 * @property string|null $provider
 * @property string|null $provider_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
     * Image fields managed by HasImageAttribute trait
     */
    protected array $imageFields = ['image'];

    protected array $imageDirectories = ['image' => 'images/users'];

    protected array $imageDefaults = ['image' => 'images/person.png'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'gender' => Gender::class,
        ];
    }

    /**
     * Scope: Filter users by role (exclude 'user' role)
     */
    public function scopeNotUser(Builder $query): Builder
    {
        return $query->whereHas('roles', static function (Builder $query) {
            $query->where('name', '!=', 'user');
        });
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin') || $this->hasRole('Super Admin');
    }

    /**
     * True for the Super Admin role — the only role allowed to impersonate
     * other users. Deliberately role-based so impersonation can never be
     * granted to another role through the permissions UI.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
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

    public function firebaseTokens(): HasMany
    {
        return $this->hasMany(FirebaseToken::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    public function loginHistory(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class);
    }

    public function latestLogin(): HasOne
    {
        return $this->hasOne(UserLoginHistory::class)->latestOfMany('logged_in_at');
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
