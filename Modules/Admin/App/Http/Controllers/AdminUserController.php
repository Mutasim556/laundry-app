<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminUserController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(): View
    {
        // Only roles with guard_name = 'admin' are eligible for Admin users
        $adminRoles = Role::where('guard_name', 'admin')->orderBy('name')->get();

        return view('admin::admins.index', compact('adminRoles'));
    }

    /**
     * Return JSON data of admins for real-time AJAX DataTable.
     */
    public function data(): JsonResponse
    {
        $currentAdminId = Auth::guard('admin')->id();
        $admins = Admin::with('roles')->latest()->get();

        $data = $admins->map(function ($admin) use ($currentAdminId) {
            $isSuperAdmin = $admin->hasRole('super-admin');
            $isCurrentAdmin = $admin->id === $currentAdminId;
            $primaryRole = $admin->roles->first();

            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar_url' => $admin->avatar_url,
                'role' => $primaryRole ? $primaryRole->name : null,
                'roles' => $admin->roles->pluck('name')->toArray(),
                'role_names' => $admin->roles->map(fn($role) => [
                    'name' => $role->name,
                    'is_super_admin' => $role->name === 'super-admin',
                ])->values()->all(),
                'created_at' => $admin->created_at ? $admin->created_at->format('M d, Y') : 'N/A',
                'is_blocked' => (bool) $admin->is_blocked,
                'is_super_admin' => $isSuperAdmin,
                'is_current_admin' => $isCurrentAdmin,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Store a newly created admin in storage via AJAX.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', Password::defaults()],
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'admin');
                }),
            ],
            'roles' => ['nullable'], // support both 'role' and 'roles'
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars/admins', 'public');
        }

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'avatar' => $avatarPath,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // One admin can have one role
        $roleToAssign = $validated['role'] ?? (is_array($request->roles) ? ($request->roles[0] ?? null) : $request->roles);
        if ($roleToAssign) {
            $admin->syncRoles([$roleToAssign]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Admin user created successfully.',
                'admin' => $admin->load('roles'),
            ], 201);
        }

        return redirect()->route('admin.admins.index')->with('success', 'Admin user created successfully.');
    }

    /**
     * Update the specified admin in storage via AJAX.
     */
    public function update(Request $request, Admin $admin): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'admin');
                }),
            ],
            'roles' => ['nullable'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists and not default
            if ($admin->avatar && Storage::disk('public')->exists($admin->avatar)) {
                Storage::disk('public')->delete($admin->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('avatars/admins', 'public');
        }

        $admin->update($updateData);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // One admin can have one role
        $roleToAssign = $validated['role'] ?? (is_array($request->roles) ? ($request->roles[0] ?? null) : $request->roles);

        // Prevent accidental lockout of the seeded super-admin role
        if ($admin->email === 'admin@laundry.com') {
            $roleToAssign = 'super-admin';
        }

        if ($roleToAssign) {
            $admin->syncRoles([$roleToAssign]);
        } else {
            $admin->syncRoles([]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Admin user updated successfully.',
                'admin' => $admin->fresh()->load('roles'),
            ]);
        }

        return redirect()->route('admin.admins.index')->with('success', 'Admin user updated successfully.');
    }

    /**
     * Assign or update role specifically for an admin user via dedicated AJAX modal.
     */
    public function updateRoles(Request $request, Admin $admin): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'admin');
                }),
            ],
            'roles' => ['nullable'],
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // One admin can have one role
        $roleToAssign = $validated['role'] ?? (is_array($request->roles) ? ($request->roles[0] ?? null) : $request->roles);

        if ($admin->email === 'admin@laundry.com') {
            $roleToAssign = 'super-admin';
        }

        if ($roleToAssign) {
            $admin->syncRoles([$roleToAssign]);
        } else {
            $admin->syncRoles([]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully to admin user.',
                'admin' => $admin->fresh()->load('roles'),
            ]);
        }

        return redirect()->route('admin.admins.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Toggle the blocked status of an admin user.
     */
    public function toggleBlock(Request $request, Admin $admin): JsonResponse|RedirectResponse
    {
        $currentAdmin = Auth::guard('admin')->user();

        // Guard: Prevent blocking logged-in admin
        if ($currentAdmin && $currentAdmin->id === $admin->id) {
            $msg = 'You cannot block your own admin account.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->route('admin.admins.index')->with('error', $msg);
        }

        // Guard: Prevent blocking master super admin
        if ($admin->email === 'admin@laundry.com') {
            $msg = 'The primary Super Admin account cannot be blocked.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->route('admin.admins.index')->with('error', $msg);
        }

        $admin->is_blocked = !$admin->is_blocked;
        $admin->save();

        $statusText = $admin->is_blocked ? 'blocked' : 'unblocked';
        $message = 'Admin user "' . $admin->name . '" has been ' . $statusText . ' successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_blocked' => (bool) $admin->is_blocked,
            ]);
        }

        return redirect()->route('admin.admins.index')->with('success', $message);
    }

    /**
     * Remove the specified admin from storage via AJAX.
     */
    public function destroy(Request $request, Admin $admin): JsonResponse|RedirectResponse
    {
        $currentAdmin = Auth::guard('admin')->user();

        // Guard: Prevent deleting logged-in admin
        if ($currentAdmin && $currentAdmin->id === $admin->id) {
            $msg = 'You cannot delete your own admin account while logged in.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->route('admin.admins.index')->with('error', $msg);
        }

        // Guard: Prevent deleting master super-admin
        if ($admin->email === 'admin@laundry.com' || ($admin->hasRole('super-admin') && Admin::role('super-admin')->count() <= 1)) {
            $msg = 'The primary Super Admin account cannot be deleted to prevent system lockout.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->route('admin.admins.index')->with('error', $msg);
        }

        if ($admin->avatar && Storage::disk('public')->exists($admin->avatar)) {
            Storage::disk('public')->delete($admin->avatar);
        }

        $admin->roles()->detach();
        $admin->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Admin user deleted successfully.',
            ]);
        }

        return redirect()->route('admin.admins.index')->with('success', 'Admin user deleted successfully.');
    }
}
