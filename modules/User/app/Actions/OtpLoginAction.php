<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otp\Enum\ContactType;
use Modules\User\Data\UserData;
use Mrj\Foundation\Support\PhoneNumber;
use Spatie\Permission\Models\Role;

/**
 * Find an existing user by contact, or silently auto-register a new one.
 *
 * New users get a random strong password (never exposed) so password-based
 * flows (changePassword, resetPassword) work normally later.
 *
 * Role falls back to 'user' when not provided or not found in the DB,
 * mirroring the HandleSocialUserAction pattern.
 */
final readonly class OtpLoginAction
{
    public function __construct(
        private CreateUserAction $createUser,
        private MarkContactVerifiedAction $markVerified,
    ) {}

    public function execute(string $contact, ContactType $contactType, ?string $roleName = null): User
    {
        $user = $contactType === ContactType::Phone
            ? User::query()->wherePhone(PhoneNumber::toE164($contact))->first()
            : User::where('email', $contact)->first();

        if ($user) {
            return $user;
        }

        // Resolve role — fall back to 'user' just like HandleSocialUserAction
        $role = Role::where('name', $roleName)->first()
            ?? Role::where('name', 'user')->first();

        // Seed a placeholder name so the account is never blank — from the email
        // local-part, or the contact itself for phone. An administrator can refine it later.
        $name = $contactType === ContactType::Email
            ? Str::of(explode('@', $contact)[0])->replace(['.', '_', '-'], ' ')->title()->toString()
            : $contact;

        // NOTE: the users table has no phone column, so a phone-only auto-registered
        // user has no number stored unless the project keeps one (User::scopeWherePhone()).
        $userData = new UserData(
            email: $contactType === ContactType::Email ? $contact : null,
            name: $name,
            password: Hash::make(Str::password(32)),
            roles: $role ? [$role->id] : [],
            is_active: true,
        );

        $user = $this->createUser->execute($userData);

        // Only email carries a verification flag.
        if ($contactType === ContactType::Email) {
            $this->markVerified->execute($user, 'email');
        }

        return $user;
    }
}
