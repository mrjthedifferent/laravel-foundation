<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\RolePermission\Http\Controllers\RolePermissionController;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function (): void {
    // Role CRUD
    Route::resource('role', RolePermissionController::class)->except(['create', 'show', 'edit']);
    Route::get('role/{role}/clone', [RolePermissionController::class, 'clone'])->name('role.clone');

    // Assign permissions to a role
    Route::get('role/{role}/assign-permission', [RolePermissionController::class, 'assignPermissionPage'])->name('role.assign.permission.get');
    Route::post('role/{role}/assign-permission', [RolePermissionController::class, 'assignPermission'])->name('role.assign.permission');

    // Permission management
    Route::get('permissions', [RolePermissionController::class, 'managePermissions'])->name('permissions.manage');
    Route::post('permissions', [RolePermissionController::class, 'storePermission'])->name('permission.store');
    Route::post('permissions/sync', [RolePermissionController::class, 'syncPermissions'])->name('permission.sync');
    Route::delete('permissions/bulk-delete', [RolePermissionController::class, 'bulkDeletePermissions'])->name('permission.bulk-delete');
    Route::delete('permissions/{permission}', [RolePermissionController::class, 'deletePermission'])->name('permission.delete');
    Route::get('permissions/{permission}/roles', [RolePermissionController::class, 'getPermissionRoles'])->name('permission.roles');
});
