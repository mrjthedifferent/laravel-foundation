<?php

namespace Modules\User\Actions;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class HandleSocialUserAction
{
    /**
     * Resolve an existing user from social provider data.
     *
     * Registration is closed: all accounts are created by an
     * administrator. Social login therefore only authenticates pre-existing users and
     * never auto-provisions a new account. Returns null when no matching,
     * active user is found so the caller can reject the login.
     */
    public function execute(string $provider, SocialiteUser $socialUser, ?string $role = null): ?User
    {
        $email = $socialUser->getEmail();

        // Match strictly on a previously-linked provider identity first, then
        // fall back to a matching email of an existing account.
        $user = User::query()
            ->where(function ($query) use ($provider, $socialUser): void {
                $query->where('provider', $provider)
                    ->where('provider_id', $socialUser->getId());
            })
            ->when($email, fn ($query) => $query->orWhere('email', $email))
            ->first();

        if (! $user) {
            return null;
        }

        $updateData = [];

        // Link the provider identity to the existing account.
        if ($user->provider_id !== $socialUser->getId() || $user->provider !== $provider) {
            $updateData['provider'] = $provider;
            $updateData['provider_id'] = $socialUser->getId();
        }

        if (empty($user->image)) {
            $updateData['image'] = $socialUser->getAvatar();
        }

        if (! $user->email_verified_at && $email) {
            $updateData['email_verified_at'] = now();
        }

        if (! empty($updateData)) {
            $user->update($updateData);
        }

        return $user;
    }
}
