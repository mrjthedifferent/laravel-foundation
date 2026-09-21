<?php

namespace Modules\RolePermission\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RolePermission\Actions\AssignPermissionAction;
use Modules\RolePermission\Actions\CloneRoleAction;
use Modules\RolePermission\Actions\DeletePermissionAction;
use Modules\RolePermission\Actions\StorePermissionAction;
use Modules\RolePermission\Actions\StoreRoleAction;
use Modules\RolePermission\Actions\SyncPermissionsAction;
use Modules\RolePermission\Actions\UpdateRoleAction;
use Modules\RolePermission\Http\Requests\AssignPermissionRequest;
use Modules\RolePermission\Http\Requests\StorePermissionRequest;
use Modules\RolePermission\Http\Requests\StoreRoleRequest;
use Modules\RolePermission\Http\Requests\UpdateRoleRequest;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    // ── Roles ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::withCount(['permissions', 'users'])
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->name.'%'))
            ->paginate(perPage());

        return view('rolepermission::index', compact('roles'));
    }

    public function store(StoreRoleRequest $request, StoreRoleAction $action): RedirectResponse
    {
        $this->authorize('create', Role::class);
        $action->execute($request->validated()['role_name']);

        return redirect()->route('admin.role.index')->with('success', 'Role created successfully.');
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        $this->authorize('update', $role);
        $action->execute($role, $request->validated()['role_name']);

        return redirect()->route('admin.role.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return redirect()->route('admin.role.index')->with('error', 'Cannot delete a role that is assigned to users.');
        }

        $role->delete();

        return redirect()->route('admin.role.index')->with('success', 'Role deleted successfully.');
    }

    public function clone(Role $role, CloneRoleAction $action): RedirectResponse
    {
        $this->authorize('clone', Role::class);

        $clone = $action->execute($role);

        return redirect()->route('admin.role.index')->with('success', 'Role cloned as "'.$clone->name.'".');
    }

    // ── Permission assignment ─────────────────────────────────────────────────

    public function assignPermissionPage(Role $role): View
    {
        $this->authorize('assignPermission', $role);

        $all_permissions = Permission::select('id', 'name', 'module_name')
            ->orderBy('module_name')
            ->orderBy('name')
            ->get()
            ->groupBy('module_name');

        return view('rolepermission::assign_permission', compact('role', 'all_permissions'));
    }

    public function assignPermission(AssignPermissionRequest $request, Role $role, AssignPermissionAction $action): RedirectResponse
    {
        $this->authorize('assignPermission', $role);

        $action->execute($role, $request->validated()['permissions'] ?? []);

        return redirect()->back()->with('success', 'Permissions assigned successfully.');
    }

    // ── Permission management ─────────────────────────────────────────────────

    public function managePermissions(Request $request): View
    {
        $this->authorize('managePermissions', Role::class);

        $permissions = Permission::select('id', 'name', 'module_name', 'description')
            ->withCount('roles')
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->name.'%'))
            ->when($request->filled('module'), fn ($q) => $q->where('module_name', $request->module))
            ->orderBy('module_name')
            ->orderBy('name')
            ->paginate(perPage());

        $modules = Permission::distinct()->pluck('module_name');

        return view('rolepermission::manage_permissions', compact('permissions', 'modules'));
    }

    public function storePermission(StorePermissionRequest $request, StorePermissionAction $action): RedirectResponse
    {
        $this->authorize('managePermissions', Role::class);

        $data = $request->validated();
        $moduleName = $data['module_name'] === 'new' ? $data['new_module_name'] : $data['module_name'];

        $action->execute($data['permission_name'], $moduleName, $data['description'] ?? null);

        return redirect()->back()->with('success', 'Permission created successfully.');
    }

    public function deletePermission(Permission $permission, DeletePermissionAction $action): RedirectResponse
    {
        $this->authorize('managePermissions', Role::class);

        $action->execute($permission);

        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }

    public function bulkDeletePermissions(Request $request, DeletePermissionAction $action): RedirectResponse
    {
        $this->authorize('managePermissions', Role::class);

        $ids = $request->input('permission_ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No permissions selected.');
        }

        Permission::whereIn('id', $ids)->get()->each(fn ($p) => $action->execute($p));

        return redirect()->back()->with('success', count($ids).' permissions deleted successfully.');
    }

    public function syncPermissions(SyncPermissionsAction $action): RedirectResponse
    {
        $this->authorize('managePermissions', Role::class);

        $action->execute();

        return redirect()->back()->with('success', 'Permissions synced successfully.');
    }

    public function getPermissionRoles(Permission $permission): JsonResponse
    {
        $this->authorize('managePermissions', Role::class);

        return JsonResponseFactory::success(
            'Roles retrieved.',
            ['roles' => $permission->roles()->get(['id', 'name'])]
        );
    }
}
