<?php

declare(strict_types=1);

namespace Mrj\Foundation\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Mrj\Foundation\Support\Email;

/**
 * The only way to make or unmake a Super Admin. A Super Admin passes every
 * permission check, so nothing in the web or API layer can grant the flag,
 * and the admin UI cannot deactivate, delete or reset one.
 *
 * @internal
 */
final class SuperAdminCommand extends Command
{
    protected $signature = 'foundation:super-admin
        {login? : Email or phone of the user to promote (or of the new account to create)}
        {--revoke : Remove the Super Admin flag instead of granting it}
        {--list : List the current Super Admins}
        {--name= : Name for a new account}
        {--password= : Password for a new account (prompted when omitted)}';

    protected $description = 'Create a Super Admin, promote an existing user to one, or revoke it';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->list();
        }

        $login = (string) ($this->argument('login') ?? $this->ask('Email (or phone) of the Super Admin'));

        if (trim($login) === '') {
            $this->components->error('An email or phone number is required.');

            return self::FAILURE;
        }

        $user = $this->find($login);

        if ($this->option('revoke')) {
            return $this->revoke($user, $login);
        }

        return $user instanceof User ? $this->promote($user) : $this->create($login);
    }

    private function find(string $login): ?User
    {
        return User::query()->where('email', Email::normalize($login))->first()
            ?? User::query()->wherePhone($login)->first();
    }

    private function promote(User $user): int
    {
        if ($user->isSuperAdmin()) {
            $this->components->info("$user->name is already a Super Admin.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Make $user->name ($user->email) a Super Admin? They will pass every permission check.", true)) {
            return self::FAILURE;
        }

        $user->forceFill(['is_super_admin' => true])->save();
        $this->components->info("$user->name is now a Super Admin.");

        return self::SUCCESS;
    }

    private function create(string $email): int
    {
        $name = (string) ($this->option('name') ?? $this->ask('Name'));
        $password = $this->option('password');

        if ($password === null) {
            $password = (string) $this->secret('Password (at least 8 characters)');

            if ($password !== (string) $this->secret('Password again')) {
                $this->components->error('The passwords do not match.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            ['name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email'], 'password' => ['required', 'string', 'min:8']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_active' => true,
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->components->info("Super Admin $user->name ($user->email) created. Sign in at ".route('login').'.');

        return self::SUCCESS;
    }

    private function revoke(?User $user, string $login): int
    {
        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            $this->components->error("$login is not a Super Admin.");

            return self::FAILURE;
        }

        $others = User::query()->where('is_super_admin', true)->whereKeyNot($user->getKey())->count();

        if ($others === 0) {
            $this->components->warn("$user->name is the last Super Admin. Without one, only roles and permissions govern access.");
        }

        if (! $this->confirm("Revoke Super Admin from $user->name ($user->email)?", $others > 0)) {
            return self::FAILURE;
        }

        $user->forceFill(['is_super_admin' => false])->save();
        $this->components->info("$user->name is no longer a Super Admin.");

        return self::SUCCESS;
    }

    private function list(): int
    {
        $admins = User::query()->where('is_super_admin', true)->orderBy('name')->get(['id', 'name', 'email', 'phone', 'is_active']);

        if ($admins->isEmpty()) {
            $this->components->warn('There is no Super Admin. Create one: php artisan foundation:super-admin');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Name', 'Email', 'Phone', 'Active'], $admins->map(fn (User $u): array => [
            $u->id, $u->name, $u->email, $u->phone, $u->is_active ? 'yes' : 'no',
        ])->all());

        return self::SUCCESS;
    }
}
