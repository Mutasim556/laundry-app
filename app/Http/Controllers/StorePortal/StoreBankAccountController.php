<?php

namespace App\Http\Controllers\StorePortal;

use App\Http\Controllers\Controller;
use App\Models\StoreBankAccount;
use App\Models\StoreUser;
use App\Services\StoreBankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreBankAccountController extends Controller
{
    public function __construct(
        protected StoreBankAccountService $bankService
    ) {}

    protected function storeUser(): StoreUser
    {
        return Auth::guard('store')->user()->load('store');
    }

    public function index(): JsonResponse
    {
        $store = $this->storeUser()->store;
        $accounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $store = $this->storeUser()->store;
        $account = $this->bankService->createForStore($store, $request->all());

        $accounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => 'Store bank account saved successfully.',
            'account' => $account->toSafeArray(),
            'accounts' => $accounts,
        ], 201);
    }

    public function update(Request $request, StoreBankAccount $bankAccount): JsonResponse
    {
        $store = $this->storeUser()->store;
        $updated = $this->bankService->updateForStore($store, $bankAccount, $request->all());

        $accounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => 'Store bank account updated successfully.',
            'account' => $updated->toSafeArray(),
            'accounts' => $accounts,
        ]);
    }

    public function setDefault(StoreBankAccount $bankAccount): JsonResponse
    {
        $store = $this->storeUser()->store;
        $updated = $this->bankService->setDefault($store, $bankAccount);

        $accounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => "{$updated->bank_name} ({$updated->account_number_masked}) is now the default store bank account.",
            'account' => $updated->toSafeArray(),
            'accounts' => $accounts,
        ]);
    }

    public function destroy(StoreBankAccount $bankAccount): JsonResponse
    {
        $store = $this->storeUser()->store;
        $this->bankService->archiveForStore($store, $bankAccount);

        $accounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => 'Store bank account archived.',
            'accounts' => $accounts,
        ]);
    }
}
