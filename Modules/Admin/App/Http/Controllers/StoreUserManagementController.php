<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StoreUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\Models\Store;

class StoreUserManagementController extends Controller
{
    public function index(Store $store): JsonResponse
    {
        $users = $store->storeUsers()->get()->map(fn (StoreUser $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone_display' => $u->phone_display ?: 'Not set',
            'role' => $u->role,
            'is_active' => (bool) $u->is_active,
            'last_login_at' => $u->last_login_at?->format('d M Y, H:i') ?? 'Never',
        ]);

        return response()->json([
            'success' => true,
            'store_id' => $store->id,
            'store_name' => $store->name,
            'users' => $users,
        ]);
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:store_users,email'],
            'phone_country_code' => ['nullable', 'string', 'max:12'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['owner', 'manager', 'staff'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $normalized = StoreUser::normalizePhone($validated['phone'] ?? null, $validated['phone_country_code'] ?? null);
        if ($normalized !== null && StoreUser::where('phone_normalized', $normalized)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This international phone number is already used by another store user.',
                'errors' => ['phone' => ['This international phone number is already used by another store user.']],
            ], 422);
        }

        $user = StoreUser::create([
            'store_id' => $store->id,
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone_country_code' => $validated['phone_country_code'] ?: null,
            'phone' => $validated['phone'] ? trim($validated['phone']) : null,
            'phone_normalized' => $normalized,
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
            'created_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Store Portal user \"{$user->name}\" created.",
            'user_id' => $user->id,
        ]);
    }

    public function toggle(Store $store, StoreUser $storeUser): JsonResponse
    {
        abort_unless((int) $storeUser->store_id === (int) $store->id, 404);

        $storeUser->update(['is_active' => ! $storeUser->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => (bool) $storeUser->is_active,
            'message' => $storeUser->is_active ? 'Store user activated.' : 'Store user deactivated.',
        ]);
    }

    public function resetPassword(Request $request, Store $store, StoreUser $storeUser): JsonResponse
    {
        abort_unless((int) $storeUser->store_id === (int) $store->id, 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $storeUser->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Password reset for \"{$storeUser->name}\".",
        ]);
    }
}
