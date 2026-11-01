<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /**
     * Display a listing of web application users.
     */
    public function index(): View
    {
        // Only roles with guard_name = 'web' are eligible for frontend application Users
        $webRoles = Role::where('guard_name', 'web')->orderBy('name')->get();

        return view('admin::users.index', compact('webRoles'));
    }

    /**
     * Return JSON data of users for real-time AJAX DataTable with optional role filter.
     */
    public function data(Request $request): JsonResponse
    {
        $roleFilter = $request->query('role');

        $query = User::with('roles')->latest();

        if (!empty($roleFilter)) {
            if ($roleFilter === 'none') {
                $query->doesntHave('roles');
            } else {
                $query->whereHas('roles', function ($q) use ($roleFilter) {
                    $q->where('name', $roleFilter)->where('guard_name', 'web');
                });
            }
        }

        $users = $query->get();

        $data = $users->map(function ($user) {
            $primaryRole = $user->roles->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'role' => $primaryRole ? $primaryRole->name : null,
                'roles' => $primaryRole ? [$primaryRole->name] : [],
                'role_names' => $primaryRole ? [
                    [
                        'name' => $primaryRole->name,
                        'is_super_admin' => false,
                    ]
                ] : [],
                'created_at' => $user->created_at ? $user->created_at->format('M d, Y') : 'N/A',
                'is_blocked' => (bool) $user->is_blocked,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Store a newly created user in storage via AJAX.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'web');
                }),
            ],
            'roles' => ['nullable'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars/users', 'public');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'avatar' => $avatarPath,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // One user can have one role
        $roleToAssign = $validated['role'] ?? (is_array($request->roles) ? ($request->roles[0] ?? null) : $request->roles);
        if ($roleToAssign) {
            $user->syncRoles([$roleToAssign]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'user' => $user->load('roles'),
            ], 201);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user in storage via AJAX.
     */
    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'web');
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
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('avatars/users', 'public');
        }

        $user->update($updateData);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // One user can have one role
        $roleToAssign = $validated['role'] ?? (is_array($request->roles) ? ($request->roles[0] ?? null) : $request->roles);
        if ($roleToAssign) {
            $user->syncRoles([$roleToAssign]);
        } else {
            $user->syncRoles([]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'user' => $user->fresh()->load('roles'),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Assign or update role specifically for a user via dedicated AJAX modal.
     */
    public function updateRoles(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'web');
                }),
            ],
            'roles' => ['nullable'],
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // One user can have one role
        $roleToAssign = $validated['role'] ?? (is_array($request->roles) ? ($request->roles[0] ?? null) : $request->roles);

        if ($roleToAssign) {
            $user->syncRoles([$roleToAssign]);
        } else {
            $user->syncRoles([]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully to user.',
                'user' => $user->fresh()->load('roles'),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Toggle the blocked status of an application user.
     */
    public function toggleBlock(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $user->is_blocked = !$user->is_blocked;
        $user->save();

        $statusText = $user->is_blocked ? 'blocked' : 'unblocked';
        $message = 'User "' . $user->name . '" has been ' . $statusText . ' successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_blocked' => (bool) $user->is_blocked,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    /**
     * Remove the specified user from storage via AJAX.
     */
    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->roles()->detach();
        $user->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
