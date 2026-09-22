<?php

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\User\Actions\BulkUploadUsersAction;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Actions\ExportUsersAction;
use Modules\User\Actions\GenerateUserBulkUploadSampleAction;
use Modules\User\Actions\ManageUserAccountAction;
use Modules\User\Actions\MarkContactVerifiedAction;
use Modules\User\Actions\ResetPasswordAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Data\UserData;
use Modules\User\Enum\AccountAction;
use Modules\User\Http\Requests\BulkUploadRequest;
use Modules\User\Http\Requests\ManageUserAccountRequest;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateStatusRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Queries\UserQuery;
use Mrj\Foundation\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Web Controller for User Management
 *
 * ARCHITECTURE PATTERN: Ultra-Thin Controller
 * - Validates requests via Form Requests
 * - Executes business logic via Action classes
 * - Returns views/redirects only
 *
 * Business logic is in:
 * - Actions: CreateUserAction, UpdateUserAction, etc.
 * - Auditing: Handled automatically by OwenIt\Auditing (observer) + auditSync/auditAttach for pivot relations
 * - OTP: VerifyOtpAction (from Otp module)
 */
class UserController extends Controller
{
    /**
     * Display paginated list of users
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('viewAny', User::class);

        $users = UserQuery::make()
            ->withRelations(['roles'])
            ->filterByRole($request->integer('role') ?: null)
            ->filterByStatus($request->input('is_active') !== null ? (bool) $request->input('is_active') : null)
            ->filterByGender($request->input('gender'))
            ->emailVerified($request->input('email_verified') !== null ? (bool) $request->input('email_verified') : null)
            ->phoneVerified($request->input('phone_verified') !== null ? (bool) $request->input('phone_verified') : null)
            ->filterByDateRange('created_at', $request->input('date_from'), $request->input('date_to'))
            ->search($request->input('search'))
            ->orderByLatest()
            ->paginate(perPage());

        $roles = Role::pluck('name', 'id');

        return view('user::user.index', compact('users', 'roles'));
    }

    /**
     * Show create user form
     */
    public function create(): Renderable
    {
        $this->authorize('create', User::class);

        $roles = Role::pluck('name', 'id');

        return view('user::user.create', compact('roles'));
    }

    /**
     * Store new user
     */
    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $this->authorize('create', User::class);

        $action->execute(UserData::from($request->validated()));

        return redirect()->route('admin.users.index')
            ->with('success', __('user::user.flash.user_created'));
    }

    /**
     * Show user details
     */
    public function show(User $user): Renderable
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'documents', 'latestLogin']);
        $loginHistory = $user->loginHistory()->latest('logged_in_at')->paginate(perPage());

        return view('user::user.view', compact('user', 'loginHistory'));
    }

    /**
     * Show edit user form
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load(['roles']);
        $roles = Role::pluck('name', 'id');

        return view('user::user.edit', compact('user', 'roles'));
    }

    /**
     * Update user
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($request->has('roles')) {
            $this->authorize('updateRoles', User::class);
        }

        $data = $request->validated();
        $data['is_active'] ??= $user->is_active;

        $action->execute($user->id, UserData::from($data));

        return back()->with('success', __('user::user.flash.user_updated'));
    }

    /**
     * Delete user
     */
    public function destroy(User $user, ManageUserAccountAction $action): RedirectResponse
    {
        $this->authorize('delete', $user);

        $result = $action->execute($user, AccountAction::Delete);

        if (! $result) {
            return back()->with('error', __('user::user.errors.delete_failed'));
        }

        return redirect()->route('admin.users.index')
            ->with('success', __('user::user.flash.user_deleted'));
    }

    /**
     * Manage account (reset/delete)
     */
    public function manageAccount(ManageUserAccountRequest $request, User $user, ManageUserAccountAction $action): RedirectResponse
    {
        $this->authorize('manageAccount', $user);

        if (app()->environment('production')) {
            return back()->with('error', __('user::user.errors.account_management_unavailable'));
        }

        $accountAction = AccountAction::from($request->validated('action'));
        $result = $action->execute($user, $accountAction);

        if ($result) {
            if ($accountAction === AccountAction::Reset) {
                return back()->with('success', __('user::user.flash.account_reset'));
            }

            return redirect()->route('admin.users.index')
                ->with('success', __('user::user.flash.account_deleted'));
        }

        return back()->with('error', __('user::user.errors.manage_account_failed'));
    }

    /**
     * The spreadsheet template for bulk upload.
     */
    public function bulkUploadSample(GenerateUserBulkUploadSampleAction $action): StreamedResponse
    {
        $this->authorize('bulkUpload', User::class);

        return $action->execute();
    }

    /**
     * Manually verify user's phone
     */
    public function verifyPhone(User $user, MarkContactVerifiedAction $action): RedirectResponse
    {
        $this->authorize('verifyContact', $user);

        if (empty($user->phone)) {
            return back()->with('error', __('user::user.errors.no_phone'));
        }

        if ($user->phone_verified_at) {
            return back()->with('info', __('user::user.flash.phone_already_verified'));
        }

        $action->execute($user, 'phone');

        return back()->with('success', __('user::user.flash.phone_verified'));
    }

    /**
     * Manually verify user's email
     */
    public function verifyEmail(User $user, MarkContactVerifiedAction $action): RedirectResponse
    {
        $this->authorize('verifyContact', $user);

        if (empty($user->email)) {
            return back()->with('error', __('user::user.errors.no_email'));
        }

        if ($user->email_verified_at) {
            return back()->with('info', __('user::user.flash.email_already_verified'));
        }

        $action->execute($user, 'email');

        return back()->with('success', __('user::user.flash.email_verified'));
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user, ResetPasswordAction $action): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $action->execute($user);

        return back()->with('success', __('user::user.flash.password_reset'));
    }

    /**
     * Update user active status
     */
    public function updateStatus(UpdateStatusRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $this->authorize('updateStatus', $user);

        $action->execute($user->id, UserData::from(['is_active' => $request->validated('is_active')]));

        return back()->with('success', __('user::user.flash.status_updated'));
    }

    /**
     * Show bulk upload form
     */
    public function bulkUploadPage(): Renderable
    {
        $this->authorize('bulkUpload', User::class);

        return view('user::user.bulk-upload');
    }

    /**
     * Process bulk user upload
     */
    public function bulkUpload(BulkUploadRequest $request, BulkUploadUsersAction $action): RedirectResponse
    {
        $this->authorize('bulkUpload', User::class);

        $action->execute(Auth::user(), $request->file('users'));

        return back()->with('success', __('user::user.flash.queued', ['type' => 'Import']));
    }

    /**
     * Export users
     */
    public function export(Request $request, ExportUsersAction $action): RedirectResponse
    {
        $this->authorize('export', User::class);

        $filters = array_merge($request->all(), ['format' => $request->input('format', 'xlsx')]);
        $action->execute($request->user(), $filters);

        return back()->with('success', __('user::user.flash.queued', ['type' => 'Export']));
    }
}
