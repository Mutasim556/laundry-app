<?php

namespace App\Http\Controllers\DeliveryPersonPortal;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPersonBankAccount;
use App\Services\DeliveryPersonBankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonBankAccountController extends Controller
{
    public function __construct(
        protected DeliveryPersonBankAccountService $bankService
    ) {}

    protected function person(): DeliveryPerson
    {
        return Auth::guard('delivery_person')->user();
    }

    public function index(): JsonResponse
    {
        $person = $this->person();
        $accounts = DeliveryPersonBankAccount::query()
            ->where('delivery_person_id', $person->id)
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
        $person = $this->person();
        $account = $this->bankService->createForPerson($person, $request->all());

        $accounts = DeliveryPersonBankAccount::query()
            ->where('delivery_person_id', $person->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => 'Bank account saved successfully.',
            'account' => $account->toSafeArray(),
            'accounts' => $accounts,
        ], 201);
    }

    public function update(Request $request, DeliveryPersonBankAccount $bankAccount): JsonResponse
    {
        $person = $this->person();
        $updated = $this->bankService->updateForPerson($person, $bankAccount, $request->all());

        $accounts = DeliveryPersonBankAccount::query()
            ->where('delivery_person_id', $person->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => 'Bank account updated successfully.',
            'account' => $updated->toSafeArray(),
            'accounts' => $accounts,
        ]);
    }

    public function setDefault(DeliveryPersonBankAccount $bankAccount): JsonResponse
    {
        $person = $this->person();
        $updated = $this->bankService->setDefault($person, $bankAccount);

        $accounts = DeliveryPersonBankAccount::query()
            ->where('delivery_person_id', $person->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => "{$updated->bank_name} ({$updated->account_number_masked}) is now your default bank account.",
            'account' => $updated->toSafeArray(),
            'accounts' => $accounts,
        ]);
    }

    public function destroy(DeliveryPersonBankAccount $bankAccount): JsonResponse
    {
        $person = $this->person();
        $this->bankService->archiveForPerson($person, $bankAccount);

        $accounts = DeliveryPersonBankAccount::query()
            ->where('delivery_person_id', $person->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'message' => 'Bank account removed.',
            'accounts' => $accounts,
        ]);
    }
}
