<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsController extends Controller
{
    /**
     * Display roles and permissions management page
     */
    public function index()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => User::role($role->name)->count(),
            ];
        });

        $permissions = $this->getGroupedPermissions();

        return Inertia::render('Admin/RolesPermissions/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionsList' => Permission::all()->pluck('name')->toArray(),
        ]);
    }

    /**
     * Create a new role
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Role created successfully.');
    }

    /**
     * Update role permissions
     */
    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Prevent editing admin role's core permissions
        if ($role->name === 'admin') {
            return back()->with('error', 'Cannot modify admin role.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:50|unique:roles,name,' . $id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if (isset($validated['name'])) {
            $role->update(['name' => $validated['name']]);
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Role updated successfully.');
    }

    /**
     * Delete a role
     */
    public function destroyRole($id)
    {
        $role = Role::findOrFail($id);

        // Prevent deleting core roles
        if (in_array($role->name, ['admin', 'user'])) {
            return back()->with('error', 'Cannot delete core roles.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    /**
     * Get users with their roles
     */
    public function users(Request $request)
    {
        $query = User::with('roles', 'permissions');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('role') && $request->role) {
            $query->role($request->role);
        }

        $users = $query->paginate(20)->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->permissions->pluck('name')->toArray(),
                'created_at' => $user->created_at->format('Y-m-d H:i'),
            ];
        });

        $roles = Role::all()->pluck('name')->toArray();

        return Inertia::render('Admin/RolesPermissions/Users', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $this->getGroupedPermissions(),
            'permissionsList' => Permission::all()->pluck('name')->toArray(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Update user roles
     */
    public function updateUserRoles(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return back()->with('success', 'User roles updated successfully.');
    }

    /**
     * Update user direct permissions
     */
    public function updateUserPermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'User permissions updated successfully.');
    }

    /**
     * Get permissions grouped by module
     */
    private function getGroupedPermissions(): array
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0];

            // Handle admin permissions
            if ($module === 'admin' && count($parts) >= 2) {
                $module = 'admin_' . $parts[1];
            }

            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $grouped[$module][] = [
                'name' => $permission->name,
                'label' => $this->getPermissionLabel($permission->name),
            ];
        }

        // Sort modules
        ksort($grouped);

        return $grouped;
    }

    /**
     * Get human-readable label for permission
     */
    private function getPermissionLabel(string $permission): string
    {
        $labels = [
            // Dashboard
            'dashboard.view' => 'View Dashboard',

            // Campaigns
            'campaigns.view' => 'View Campaigns',
            'campaigns.create' => 'Create Campaigns',
            'campaigns.edit' => 'Edit Campaigns',
            'campaigns.delete' => 'Delete Campaigns',
            'campaigns.pause' => 'Pause Campaigns',
            'campaigns.resume' => 'Resume Campaigns',
            'campaigns.export' => 'Export Campaigns',

            // Domains
            'domains.view' => 'View Domains',
            'domains.create' => 'Create Domains',
            'domains.edit' => 'Edit Domains',
            'domains.delete' => 'Delete Domains',

            // Marketing
            'marketing.view' => 'View Marketing Pages',
            'marketing.home' => 'View Homepage',
            'marketing.pricing' => 'View Pricing',
            'marketing.resources' => 'View Resources',
            'marketing.blog' => 'View Blog',

            // Admin permissions
            'admin.dashboard.view' => 'View Admin Dashboard',
            'admin.users.view' => 'View All Users',
            'admin.users.edit' => 'Edit Users',
            'admin.roles.view' => 'View Roles',
            'admin.roles.edit' => 'Edit Roles',
            'admin.permissions.view' => 'View Permissions',
            'admin.permissions.assign' => 'Assign Permissions',
        ];

        return $labels[$permission] ?? ucwords(str_replace(['.', '_'], ' ', $permission));
    }
}
