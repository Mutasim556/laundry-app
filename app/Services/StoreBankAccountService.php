<?php

namespace App\Services;

use App\Models\StoreBankAccount;
use App\Models\StoreWithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\Store;

class StoreBankAccountService
{
    public function __construct(
        protected DeliveryPersonBankAccountService $sharedBankService
    ) {}

    public function getCountriesMeta(): array
    {
        return $this->sharedBankService->getCountriesMeta();
    }

    public function createForStore(Store $store, array $input): StoreBankAccount
    {
        $validated = $this->sharedBankService->validatePayload($input);

        return DB::transaction(function () use ($store, $validated) {
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();

            $hasExisting = StoreBankAccount::query()
                ->where('store_id', $store->id)
                ->where('is_active', true)
                ->exists();

            $makeDefault = ! $hasExisting || ! empty($validated['is_default']);

            if ($makeDefault) {
                StoreBankAccount::query()
                    ->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->update(['is_default' => false]);
            }

            $rawAcc = $validated['account_number'];

            return StoreBankAccount::create([
                'store_id' => $store->id,
                'account_holder_name' => trim($validated['account_holder_name']),
                'bank_name' => trim($validated['bank_name']),
                'country_code' => $validated['country_code'],
                'currency' => $validated['currency'],
                'account_type' => $validated['account_type'] ?? null,
                'account_number_encrypted' => $rawAcc,
                'account_number_last4' => substr($rawAcc, -4),
                'account_number_masked' => $this->sharedBankService->maskAccountNumber($rawAcc),
                'iban_encrypted' => $validated['iban'] ?? null,
                'routing_code_encrypted' => $validated['routing_code'] ?: null,
                'routing_code_label' => $validated['routing_code_label'] ?? null,
                'swift_bic' => ! empty($validated['swift_bic']) ? strtoupper(trim($validated['swift_bic'])) : null,
                'is_default' => $makeDefault,
                'is_active' => true,
                'verification_status' => StoreBankAccount::VERIFICATION_UNVERIFIED,
            ]);
        });
    }

    public function updateForStore(Store $store, StoreBankAccount $account, array $input): StoreBankAccount
    {
        $this->assertOwnership($store, $account);

        if (empty($input['account_number'])) {
            $input['account_number'] = (string) $account->account_number_encrypted;
        }
        if (! array_key_exists('routing_code', $input) || $input['routing_code'] === null || $input['routing_code'] === '') {
            $input['routing_code'] = $account->routing_code_encrypted ?: null;
        }

        $validated = $this->sharedBankService->validatePayload($input);

        return DB::transaction(function () use ($store, $account, $validated) {
            $locked = StoreBankAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            if (! empty($validated['is_default'])) {
                StoreBankAccount::query()
                    ->where('store_id', $store->id)
                    ->where('id', '!=', $locked->id)
                    ->update(['is_default' => false]);
            }

            $rawAcc = $validated['account_number'];
            $locked->update([
                'account_holder_name' => trim($validated['account_holder_name']),
                'bank_name' => trim($validated['bank_name']),
                'country_code' => $validated['country_code'],
                'currency' => $validated['currency'],
                'account_type' => $validated['account_type'] ?? null,
                'account_number_encrypted' => $rawAcc,
                'account_number_last4' => substr($rawAcc, -4),
                'account_number_masked' => $this->sharedBankService->maskAccountNumber($rawAcc),
                'iban_encrypted' => $validated['iban'] ?? $locked->iban_encrypted,
                'routing_code_encrypted' => $validated['routing_code'] ?: null,
                'routing_code_label' => $validated['routing_code_label'] ?? null,
                'swift_bic' => ! empty($validated['swift_bic']) ? strtoupper(trim($validated['swift_bic'])) : null,
                'is_default' => ! empty($validated['is_default']) ? true : $locked->is_default,
            ]);

            return $locked->fresh();
        });
    }

    public function setDefault(Store $store, StoreBankAccount $account): StoreBankAccount
    {
        $this->assertOwnership($store, $account);

        if (! $account->is_active) {
            throw ValidationException::withMessages(['bank_account' => ['Cannot set an archived bank account as default.']]);
        }

        return DB::transaction(function () use ($store, $account) {
            StoreBankAccount::query()
                ->where('store_id', $store->id)
                ->update(['is_default' => false]);

            $account->is_default = true;
            $account->save();

            return $account->fresh();
        });
    }

    public function archiveForStore(Store $store, StoreBankAccount $account): void
    {
        $this->assertOwnership($store, $account);

        $hasActiveWithdrawals = StoreWithdrawalRequest::query()
            ->where('bank_account_id', $account->id)
            ->whereIn('status', [
                StoreWithdrawalRequest::STATUS_PENDING,
                StoreWithdrawalRequest::STATUS_APPROVED,
            ])
            ->exists();

        if ($hasActiveWithdrawals) {
            throw ValidationException::withMessages([
                'bank_account' => ['This bank account has pending or approved withdrawal requests and cannot be removed until they are resolved.'],
            ]);
        }

        DB::transaction(function () use ($store, $account) {
            $wasDefault = (bool) $account->is_default;
            $account->update([
                'is_active' => false,
                'is_default' => false,
                'archived_at' => now(),
            ]);

            if ($wasDefault) {
                $next = StoreBankAccount::query()
                    ->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();
                if ($next) {
                    $next->is_default = true;
                    $next->save();
                }
            }
        });
    }

    public function assertOwnership(Store $store, StoreBankAccount $account): void
    {
        if ((int) $account->store_id !== (int) $store->id) {
            abort(403, 'You do not have permission to access this bank account.');
        }
    }
}
