<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(): View
    {
        $permissions = Permission::all();

        // Group permissions by guard_name first, then by group_name
        $adminPermissionsGrouped = $permissions->where('guard_name', 'admin')->groupBy(function ($permission) {
            return !empty($permission->group_name) ? $permission->group_name : 'General';
        });

        $webPermissionsGrouped = $permissions->where('guard_name', 'web')->groupBy(function ($permission) {
            return !empty($permission->group_name) ? $permission->group_name : 'General';
        });

        return view('admin::roles.index', compact('adminPermissionsGrouped', 'webPermissionsGrouped'));
    }

    /**
     * Return JSON data of roles for real-time AJAX DataTable.
     */
    public function data(): JsonResponse
    {
        $roles = Role::with(['permissions'])->withCount(['permissions'])->get();

        $data = $roles->map(function ($role) {
            // Spatie's Role::withCount('users') only counts default guard (web/User).
            // Count users polymorphically based on the role's guard_name (Admin for admin, User for web).
            $usersCount = $role->users()->count();

            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'users_count' => $usersCount,
                'permissions_count' => $role->permissions_count,
                'permission_names' => $role->permissions->pluck('name')->toArray(),
                'created_at' => $role->created_at ? $role->created_at->format('M d, Y') : 'N/A',
                'is_super_admin' => $role->name === 'super-admin',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Return JSON list of users assigned to this role by guard.
     */
    public function users(Role $role): JsonResponse
    {
        $users = $role->users()->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name ?? 'N/A',
                'email' => $user->email ?? 'N/A',
                'avatar_url' => method_exists($user, 'getAvatarUrlAttribute') ? $user->avatar_url : asset('admin/assets/images/users/1.jpg'),
                'created_at' => $user->created_at ? $user->created_at->format('M d, Y') : 'N/A',
            ];
        });

        return response()->json([
            'role' => $role->name,
            'guard' => $role->guard_name,
            'count' => $users->count(),
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created role in storage via AJAX.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', $request->guard_name),
            ],
            'guard_name' => ['required', 'string', Rule::in(['admin', 'web'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where(function ($query) use ($request) {
                    $query->where('guard_name', $request->guard_name);
                }),
            ],
        ], [
            'name.unique' => 'A role with this name already exists for the selected guard.',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'],
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role "' . $role->name . '" created successfully.',
                'role' => $role,
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role "' . $role->name . '" created successfully.');
    }

    /**
     * Update the specified role in storage via AJAX.
     */
    public function update(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id)->where('guard_name', $request->guard_name),
            ],
            'guard_name' => ['required', 'string', Rule::in(['admin', 'web'])],
        ], [
            'name.unique' => 'A role with this name already exists for the selected guard.',
        ]);

        // Prevent renaming the core super-admin role
        if ($role->name === 'super-admin' && $validated['name'] !== 'super-admin') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The core "super-admin" role name cannot be modified.',
                ], 422);
            }
            return redirect()->route('admin.roles.index')->with('error', 'The core "super-admin" role name cannot be modified.');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role "' . $role->name . '" updated successfully.',
                'role' => $role,
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role "' . $role->name . '" updated successfully.');
    }

    /**
     * Update permissions assigned to a role via AJAX.
     */
    public function updatePermissions(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where(function ($query) use ($role) {
                    $query->where('guard_name', $role->guard_name);
                }),
            ],
        ], [
            'permissions.*.exists' => 'One or more selected permissions do not belong to the role\'s guard (' . $role->guard_name . ').',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Retrieve permissions matching the role's guard strictly
        $permissions = [];
        if (!empty($validated['permissions'])) {
            $permissions = Permission::where('guard_name', $role->guard_name)
                ->whereIn('name', $validated['permissions'])
                ->get();
        }

        $role->syncPermissions($permissions);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Permissions for "' . $role->name . '" updated successfully.',
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Permissions for "' . $role->name . '" updated successfully.');
    }

    /**
     * Remove the specified role from storage via AJAX.
     */
    public function destroy(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        if ($role->name === 'super-admin') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The "super-admin" role cannot be deleted.',
                ], 422);
            }
            return redirect()->route('admin.roles.index')->with('error', 'The "super-admin" role cannot be deleted.');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roleName = $role->name;
        $role->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role "' . $roleName . '" has been deleted.',
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role "' . $roleName . '" has been deleted.');
    }
}
