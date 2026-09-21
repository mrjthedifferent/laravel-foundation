<?php

namespace Modules\User\Services;

use Modules\User\Actions\CreateUserAction;
use Modules\User\Data\UserData;
use Spatie\Permission\Models\Role;

/**
 * Validates and processes a single row from a bulk-upload spreadsheet.
 *
 * Extracted from UserBulkUploadJob so the job stays thin and this
 * per-row logic can be independently tested.
 */
final readonly class BulkUserRowProcessor
{
    public function __construct(
        private CreateUserAction $createUser,
    ) {}

    /**
     * Process one spreadsheet row.
     *
     * A user account is keyed by email.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $existingEmails  Already-known emails (mutated in place on success)
     * @param  int  $rowNo  1-based row number for error messages
     * @return string|null Error message on failure, null on success
     */
    public function process(array $row, array &$existingEmails, int $rowNo): ?string
    {
        $email = trim((string) ($row['email'] ?? ''));

        if ($email === '') {
            return null; // silently skip rows with no email
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Email: {$email} is invalid at row: {$rowNo}";
        }

        if (in_array($email, $existingEmails, true)) {
            return "Email: {$email} already exists at row: {$rowNo}";
        }

        if (empty($row['role'])) {
            return "Role is required at row: {$rowNo}";
        }

        $roleIds = Role::whereIn('name', explode(',', (string) $row['role']))->pluck('id')->toArray();

        if (empty($roleIds)) {
            return "Role is invalid at row: {$rowNo}";
        }

        try {
            $this->createUser->execute(UserData::from([
                'name' => trim((string) ($row['name'] ?? '')) ?: (trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')) ?: 'User'),
                'email' => $email,
                'password' => $row['password'] ?? null,
                'password_confirmation' => $row['password'] ?? null,
                'is_active' => $row['is_active'] ?? true,
                'gender' => $row['gender'] ?? 'male',
                'roles' => $roleIds,
            ]));

            $existingEmails[] = $email;

            return null;
        } catch (\Exception $e) {
            return $e->getMessage()." at row: {$rowNo}";
        }
    }
}
