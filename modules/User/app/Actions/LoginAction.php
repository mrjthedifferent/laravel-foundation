<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;
use Mrj\Foundation\Support\PhoneNumber;

/**
 * Validates credentials and returns the authenticated user.
 *
 * Extracts credential-checking logic from the API controller
 * so it can be independently tested and reused.
 *
 * @throws UnauthorizedException
 */
final readonly class LoginAction
{
    /**
     * Attempt to find and authenticate a user by email or phone + password.
     *
     * Returns null on any credential failure so the caller decides the response.
     */
    public function execute(string $identifier, string $password): ?User
    {
        $user = User::where('email', $identifier)
            ->orWhere(fn ($q) => $q->wherePhone(PhoneNumber::toE164($identifier)))
            ->first();

        if (! $user) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
