<?php

declare(strict_types=1);

return [
    'index' => [
        'breadcrumb' => 'Roles',
        'search_placeholder' => 'Role name…',
        'empty' => 'No roles found',
        'manage_permissions' => 'Manage Permissions',
        'create_role' => 'Create Role',
        'col_permissions_assigned' => 'Permissions Assigned',
        'col_users_assigned' => 'Users Assigned',
        'assign_permissions' => 'Assign Permissions',
        'clone_role' => 'Clone Role',
        'clone_role_confirm' => 'Clone this role with all its permissions?',
        'delete_role' => 'Delete Role',
        'delete_role_confirm' => 'Delete this role? This action cannot be undone.',
        'role_name_label' => 'Role Name',
        'role_name_placeholder' => 'Role name',
        'edit_role' => 'Edit Role',
        'update_role' => 'Update Role',
    ],

    'manage_permissions' => [
        'title' => 'Permissions',
        'permission_name_label' => 'Permission Name',
        'search_placeholder' => 'Search by name…',
        'module_label' => 'Module',
        'all_modules' => 'All Modules',
        'empty' => 'No permissions found',
        'delete_selected' => 'Delete Selected',
        'create_permission' => 'Create Permission',
        'sync_permissions' => 'Sync Permissions',
        'sync_permissions_confirm' => "Sync permissions? This adds any new permissions from the modules' config files.",
        'col_used_by' => 'Used By',
        'roles_count' => ':count roles',
        'view_roles_title' => 'View roles',
        'delete_permission_confirm' => 'Delete this permission? May break functionality if in use.',
        'module_name_label' => 'Module Name',
        'select_module' => 'Select Module',
        'create_new_module' => '+ Create New Module',
        'new_module_name_label' => 'New Module Name',
        'description_optional_label' => 'Description (Optional)',
        'roles_using_permission_title' => 'Roles Using This Permission',
        'bulk_delete_confirm_title' => 'Delete selected permissions?',
        'bulk_delete_confirm_text' => 'May break functionality if in use.',
        'no_roles_using_permission' => 'No roles using this permission.',
        'load_failed' => 'Failed to load.',
    ],

    'assign_permission' => [
        'breadcrumb' => 'Assign Permissions — :role',
        'subtitle' => 'Role: :role',
        'back_to_roles' => 'Back to Roles',
        'check_all' => 'Check All',
        'save_permissions' => 'Save Permissions',
        'toggle_module' => 'Toggle all in :module',
    ],

    'layouts' => [
        'label' => 'Roles & Permissions',
    ],

    'widget' => [
        'view_all' => 'View all',
        'roles_label' => 'Roles',
        'permissions_label' => 'Permissions',
    ],

    'flash' => [
        'role_created' => 'Role created successfully.',
        'role_updated' => 'Role updated successfully.',
        'role_has_users' => 'Cannot delete a role that is assigned to users.',
        'role_deleted' => 'Role deleted successfully.',
        'role_cloned' => 'Role cloned as ":name".',
        'permissions_assigned' => 'Permissions assigned successfully.',
        'permission_created' => 'Permission created successfully.',
        'permission_deleted' => 'Permission deleted successfully.',
        'no_permissions_selected' => 'No permissions selected.',
        'permissions_deleted_count' => ':count permissions deleted successfully.',
        'permissions_synced' => 'Permissions synced successfully.',
        'roles_retrieved' => 'Roles retrieved.',
    ],

    'validation' => [
        'permission_name_unique' => 'This permission name already exists.',
        'module_name_required_without' => 'Please select a module or create a new one.',
        'new_module_name_required_if' => 'Please enter a name for the new module.',
        'role_name_required' => 'The role name is required.',
        'role_name_unique' => 'This role name is already taken.',
    ],
];
