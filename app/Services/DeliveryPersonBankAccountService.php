<?php

namespace App\Services;

use App\Models\DeliveryPersonBankAccount;
use App\Models\DeliveryPersonWithdrawalRequest;
use App\Support\CurrencyCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonBankAccountService
{
    public const COUNTRIES = [
        'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'routing_label' => 'Sort Code', 'routing_required' => true, 'routing_regex' => '/^\d{6}$/', 'routing_hint' => '6 digits (e.g. 12-34-56)', 'iban_optional' => true, 'swift_optional' => true],
        'US' => ['name' => 'United States', 'currency' => 'USD', 'routing_label' => 'Routing Number (ABA)', 'routing_required' => true, 'routing_regex' => '/^\d{9}$/', 'routing_hint' => '9 digits', 'iban_optional' => false, 'swift_optional' => true],
        'CA' => ['name' => 'Canada', 'currency' => 'CAD', 'routing_label' => 'Transit & Institution No.', 'routing_required' => true, 'routing_regex' => '/^[A-Za-z0-9\-]{5,12}$/', 'routing_hint' => '5-12 chars', 'iban_optional' => false, 'swift_optional' => true],
        'AU' => ['name' => 'Australia', 'currency' => 'AUD', 'routing_label' => 'BSB Code', 'routing_required' => true, 'routing_regex' => '/^\d{6}$/', 'routing_hint' => '6 digits (e.g. 062-000)', 'iban_optional' => false, 'swift_optional' => true],
        'IN' => ['name' => 'India', 'currency' => 'INR', 'routing_label' => 'IFSC Code', 'routing_required' => true, 'routing_regex' => '/^[A-Z]{4}0[A-Z0-9]{6}$/i', 'routing_hint' => '11 chars (e.g. HDFC0001234)', 'iban_optional' => false, 'swift_optional' => true],
        'BD' => ['name' => 'Bangladesh', 'currency' => 'BDT', 'routing_label' => 'Routing / Branch Code', 'routing_required' => false, 'routing_regex' => '/^[A-Za-z0-9\-]{3,20}$/', 'routing_hint' => 'Optional 9-digit routing number', 'iban_optional' => false, 'swift_optional' => true],
        'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED', 'routing_label' => 'Branch Code', 'routing_required' => false, 'routing_regex' => null, 'routing_hint' => 'Optional', 'iban_optional' => true, 'swift_optional' => true],
        'IE' => ['name' => 'Ireland', 'currency' => 'EUR', 'routing_label' => 'Sort / NSC Code', 'routing_required' => false, 'routing_regex' => null, 'routing_hint' => 'Optional', 'iban_optional' => true, 'swift_optional' => true],
        'DE' => ['name' => 'Germany', 'currency' => 'EUR', 'routing_label' => 'BLZ (Optional)', 'routing_required' => false, 'routing_regex' => null, 'routing_hint' => 'Optional', 'iban_optional' => true, 'swift_optional' => true],
        'FR' => ['name' => 'France', 'currency' => 'EUR', 'routing_label' => 'RIB Code (Optional)', 'routing_required' => false, 'routing_regex' => null, 'routing_hint' => 'Optional', 'iban_optional' => true, 'swift_optional' => true],
        'SG' => ['name' => 'Singapore', 'currency' => 'SGD', 'routing_label' => 'Bank / Branch Code', 'routing_required' => false, 'routing_regex' => null, 'routing_hint' => 'Optional', 'iban_optional' => false, 'swift_optional' => true],
        'PK' => ['name' => 'Pakistan', 'currency' => 'PKR', 'routing_label' => 'Branch Code', 'routing_required' => false, 'routing_regex' => null, 'routing_hint' => 'Optional', 'iban_optional' => true, 'swift_optional' => true],
    ];

    public function getCountriesMeta(): array
    {
        return self::COUNTRIES;
    }

    public function maskAccountNumber(string $raw): string
    {
        $clean = preg_replace('/\s+/', '', trim($raw)) ?? '';
        $len = strlen($clean);
        if ($len <= 4) {
            return '•••• ' . $clean;
        }

        return '•••• ' . substr($clean, -4);
    }

    public function validatePayload(array $input): array
    {
        $country = strtoupper(trim((string) ($input['country_code'] ?? 'GB')));
        $meta = self::COUNTRIES[$country] ?? [
            'name' => $country,
            'currency' => CurrencyCatalog::COUNTRY_DEFAULT_CURRENCY[$country] ?? 'GBP',
            'routing_label' => 'Routing / Branch Code',
            'routing_required' => false,
            'routing_regex' => null,
            'iban_optional' => true,
            'swift_optional' => true,
        ];

        $currency = strtoupper(trim((string) ($input['currency'] ?? $meta['currency'])));
        $normalizedRouting = isset($input['routing_code'])
            ? preg_replace('/[\s\-]+/', '', trim((string) $input['routing_code']))
            : null;
        $normalizedAccount = preg_replace('/[\s\-]+/', '', trim((string) ($input['account_number'] ?? '')));
        $normalizedIban = isset($input['iban']) && trim((string) $input['iban']) !== ''
            ? strtoupper(preg_replace('/\s+/', '', trim((string) $input['iban'])))
            : null;

        $dataToValidate = array_merge($input, [
            'country_code' => $country,
            'currency' => $currency,
            'account_number' => $normalizedAccount,
            'routing_code' => $normalizedRouting,
            'iban' => $normalizedIban,
        ]);

        $rules = [
            'account_holder_name' => ['required', 'string', 'min:2', 'max:160'],
            'bank_name' => ['required', 'string', 'min:2', 'max:160'],
            'country_code' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys(CurrencyCatalog::CURRENCIES))],
            'account_type' => ['nullable', Rule::in(['checking', 'savings', 'current', 'business'])],
            'account_number' => ['required', 'string', 'min:4', 'max:34', 'regex:/^[A-Za-z0-9]+$/'],
            'iban' => ['nullable', 'string', 'min:10', 'max:34', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/'],
            'swift_bic' => ['nullable', 'string', 'min:8', 'max:11', 'regex:/^[A-Z0-9]{8,11}$/i'],
            'is_default' => ['nullable', 'boolean'],
        ];

        $routingRules = $meta['routing_required'] ? ['required', 'string'] : ['nullable', 'string', 'max:30'];
        if (! empty($meta['routing_regex']) && ($meta['routing_required'] || ($normalizedRouting !== null && $normalizedRouting !== ''))) {
            $routingRules[] = 'regex:' . $meta['routing_regex'];
        }
        $rules['routing_code'] = $routingRules;

        $messages = [
            'routing_code.required' => "{$meta['routing_label']} is required for {$meta['name']}.",
            'routing_code.regex' => "Invalid {$meta['routing_label']} format ({$meta['routing_hint']}).",
            'account_number.regex' => 'Account number may only contain letters and digits.',
        ];

        $validated = Validator::make($dataToValidate, $rules, $messages)->validate();
        $validated['routing_code_label'] = $meta['routing_label'];

        return $validated;
    }

    public function createForPerson(DeliveryPerson $person, array $input): DeliveryPersonBankAccount
    {
        $validated = $this->validatePayload($input);

        return DB::transaction(function () use ($person, $validated) {
            DeliveryPerson::whereKey($person->id)->lockForUpdate()->firstOrFail();

            $hasExisting = DeliveryPersonBankAccount::query()
                ->where('delivery_person_id', $person->id)
                ->where('is_active', true)
                ->exists();

            $makeDefault = ! $hasExisting || ! empty($validated['is_default']);

            if ($makeDefault) {
                DeliveryPersonBankAccount::query()
                    ->where('delivery_person_id', $person->id)
                    ->where('is_active', true)
                    ->update(['is_default' => false]);
            }

            $rawAcc = $validated['account_number'];
            $last4 = substr($rawAcc, -4);

            return DeliveryPersonBankAccount::create([
                'delivery_person_id' => $person->id,
                'account_holder_name' => trim($validated['account_holder_name']),
                'bank_name' => trim($validated['bank_name']),
                'country_code' => $validated['country_code'],
                'currency' => $validated['currency'],
                'account_type' => $validated['account_type'] ?? null,
                'account_number_encrypted' => $rawAcc,
                'account_number_last4' => $last4,
                'account_number_masked' => $this->maskAccountNumber($rawAcc),
                'iban_encrypted' => $validated['iban'] ?? null,
                'routing_code_encrypted' => $validated['routing_code'] ?: null,
                'routing_code_label' => $validated['routing_code_label'] ?? null,
                'swift_bic' => ! empty($validated['swift_bic']) ? strtoupper(trim($validated['swift_bic'])) : null,
                'is_default' => $makeDefault,
                'is_active' => true,
                'verification_status' => DeliveryPersonBankAccount::VERIFICATION_UNVERIFIED,
            ]);
        });
    }

    public function updateForPerson(DeliveryPerson $person, DeliveryPersonBankAccount $account, array $input): DeliveryPersonBankAccount
    {
        $this->assertOwnership($person, $account);

        // If account_number is left blank on edit, preserve existing encrypted account number
        if (empty($input['account_number'])) {
            $input['account_number'] = (string) $account->account_number_encrypted;
        }
        if (! array_key_exists('routing_code', $input) || $input['routing_code'] === null || $input['routing_code'] === '') {
            $input['routing_code'] = $account->routing_code_encrypted ?: null;
        }

        $validated = $this->validatePayload($input);

        return DB::transaction(function () use ($person, $account, $validated) {
            $locked = DeliveryPersonBankAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            if (! empty($validated['is_default'])) {
                DeliveryPersonBankAccount::query()
                    ->where('delivery_person_id', $person->id)
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
                'account_number_masked' => $this->maskAccountNumber($rawAcc),
                'iban_encrypted' => $validated['iban'] ?? $locked->iban_encrypted,
                'routing_code_encrypted' => $validated['routing_code'] ?: null,
                'routing_code_label' => $validated['routing_code_label'] ?? null,
                'swift_bic' => ! empty($validated['swift_bic']) ? strtoupper(trim($validated['swift_bic'])) : null,
                'is_default' => ! empty($validated['is_default']) ? true : $locked->is_default,
            ]);

            return $locked->fresh();
        });
    }

    public function setDefault(DeliveryPerson $person, DeliveryPersonBankAccount $account): DeliveryPersonBankAccount
    {
        $this->assertOwnership($person, $account);

        if (! $account->is_active) {
            throw ValidationException::withMessages(['bank_account' => ['Cannot set an archived bank account as default.']]);
        }

        return DB::transaction(function () use ($person, $account) {
            DeliveryPersonBankAccount::query()
                ->where('delivery_person_id', $person->id)
                ->update(['is_default' => false]);

            $account->is_default = true;
            $account->save();

            return $account->fresh();
        });
    }

    public function archiveForPerson(DeliveryPerson $person, DeliveryPersonBankAccount $account): void
    {
        $this->assertOwnership($person, $account);

        $hasActiveWithdrawals = DeliveryPersonWithdrawalRequest::query()
            ->where('bank_account_id', $account->id)
            ->whereIn('status', [
                DeliveryPersonWithdrawalRequest::STATUS_PENDING,
                DeliveryPersonWithdrawalRequest::STATUS_APPROVED,
            ])
            ->exists();

        if ($hasActiveWithdrawals) {
            throw ValidationException::withMessages([
                'bank_account' => ['This bank account has pending or approved withdrawal requests and cannot be removed until they are resolved.'],
            ]);
        }

        DB::transaction(function () use ($person, $account) {
            $wasDefault = (bool) $account->is_default;
            $account->update([
                'is_active' => false,
                'is_default' => false,
                'archived_at' => now(),
            ]);

            if ($wasDefault) {
                $next = DeliveryPersonBankAccount::query()
                    ->where('delivery_person_id', $person->id)
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

    public function assertOwnership(DeliveryPerson $person, DeliveryPersonBankAccount $account): void
    {
        if ((int) $account->delivery_person_id !== (int) $person->id) {
            abort(403, 'You do not have permission to access this bank account.');
        }
    }
}
