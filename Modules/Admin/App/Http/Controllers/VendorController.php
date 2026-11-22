<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Support\CountryDialCodes;
use App\Support\InternationalAddressHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Vendor;

class VendorController extends Controller
{
    /**
     * Display Vendors management index page.
     */
    public function index()
    {
        $stores = Store::orderBy('name')->get(['id', 'name', 'store_code', 'vendor_id', 'ownership_type']);
        $services = Service::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $countries = CountryDialCodes::all();
        $globalSettings = app(CurrencyService::class)->getGlobalSettings();
        $addressConfigMap = InternationalAddressHelper::addressConfigMap();

        $allVendors = Vendor::withCount('stores')->get(['id', 'status', 'country_name']);
        $stats = [
            'total' => $allVendors->count(),
            'active' => $allVendors->where('status', 'active')->count(),
            'inactive' => $allVendors->where('status', '!=', 'active')->count(),
            'linked_stores' => (int) $allVendors->sum('stores_count'),
        ];

        $filterCountries = $allVendors->pluck('country_name')->filter()->unique()->sort()->values();

        return view('admin::vendors.index', compact(
            'stores',
            'services',
            'countries',
            'globalSettings',
            'addressConfigMap',
            'stats',
            'filterCountries'
        ));
    }

    /**
     * Return JSON data for Vendors DataTable.
     */
    public function data(): JsonResponse
    {
        $currencySymbol = app(CurrencyService::class)->getGlobalSettings()['currency_symbol'] ?? '£';

        $vendors = Vendor::with(['stores:id,vendor_id,name,store_code'])
            ->withCount('stores')
            ->latest('id')
            ->get();

        $data = $vendors->map(function (Vendor $vendor) use ($currencySymbol) {
            $displayCode = $vendor->vendor_code ?: ('VND-' . str_pad((string) $vendor->id, 4, '0', STR_PAD_LEFT));
            $phoneFull = trim(($vendor->phone_country_code ? $vendor->phone_country_code . ' ' : '') . $vendor->phone);

            return [
                'id' => $vendor->id,
                'vendor_code' => $displayCode,
                'name' => $vendor->name,
                'owner_name' => $vendor->owner_name,
                'business_type' => $vendor->business_type ?? '',
                'phone_country_code' => $vendor->phone_country_code ?? '',
                'phone' => $vendor->phone,
                'phone_display' => $phoneFull,
                'alt_phone_country_code' => $vendor->alt_phone_country_code ?? '',
                'alt_phone' => $vendor->alt_phone ?? '',
                'email' => $vendor->email ?? '',
                'country_code' => $vendor->country_code ?? '',
                'country_name' => $vendor->country_name ?? '',
                'address_line_1' => $vendor->address_line_1 ?? '',
                'address_line_2' => $vendor->address_line_2 ?? '',
                'city' => $vendor->city ?? '',
                'state_region' => $vendor->state_region ?? '',
                'postcode' => $vendor->postcode ?? '',
                'business_address' => $vendor->business_address ?? '',
                'trade_license_number' => $vendor->trade_license_number ?? '',
                'tax_vat_number' => $vendor->tax_vat_number ?? '',
                'description' => $vendor->description ?? '',
                'trade_license_document_url' => $vendor->trade_license_document ? asset('storage/' . $vendor->trade_license_document) : null,
                'status' => $vendor->status,
                'verification_notes' => $vendor->verification_notes ?? '',
                'commission_type' => $vendor->commission_type,
                'commission_rate' => (float) $vendor->commission_rate,
                'payment_terms' => $vendor->payment_terms ?? '',
                'service_ids' => $vendor->service_ids ?? [],
                'payout_method' => $vendor->payout_method ?? '',
                'payout_details' => $vendor->payout_details ?? '',
                'stores_count' => $vendor->stores_count,
                'assigned_store_ids' => $vendor->stores->pluck('id')->values()->all(),
                'linked_store_names' => $vendor->stores->pluck('name')->values()->all(),
                'currency_symbol' => $currencySymbol,
                'created_at' => $vendor->created_at ? $vendor->created_at->format('M d, Y') : 'Not provided',
                'updated_at' => $vendor->updated_at ? $vendor->updated_at->format('M d, Y') : 'Not provided',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Store new Vendor.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_code' => 'nullable|string|max:50|unique:vendors,vendor_code',
            'name' => 'required|string|max:190',
            'owner_name' => 'required|string|max:120',
            'business_type' => 'nullable|string|max:80',
            'phone_country_code' => 'nullable|string|max:10',
            'phone' => 'required|string|max:30',
            'alt_phone_country_code' => 'nullable|string|max:10',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120|unique:vendors,email',
            'country_code' => 'nullable|string|max:4',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'state_region' => 'nullable|string|max:120',
            'postcode' => 'nullable|string|max:30',
            'business_address' => 'nullable|string',
            'trade_license_number' => 'nullable|string|max:100',
            'tax_vat_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'status' => 'required|in:pending,active,suspended,closed',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'required|in:percentage,fixed',
            'commission_rate' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:120',
            'service_ids' => 'nullable|array',
            'assigned_store_ids' => 'nullable|array',
            'assigned_store_ids.*' => 'exists:stores,id',
            'payout_method' => 'nullable|string|max:80',
            'payout_details' => 'nullable|string',
        ]);

        $validated['phone'] = InternationalAddressHelper::validatePhone($validated['phone'], 'phone') ?? $validated['phone'];
        if (! empty($validated['alt_phone'])) {
            $validated['alt_phone'] = InternationalAddressHelper::validatePhone($validated['alt_phone'], 'alt_phone');
        }

        $countryCode = ! empty($validated['country_code']) ? strtoupper(trim($validated['country_code'])) : null;
        if ($countryCode) {
            $countryMeta = InternationalAddressHelper::findCountry($countryCode);
            $validated['country_code'] = $countryCode;
            $validated['country_name'] = $countryMeta['name'] ?? $countryCode;
        }

        if ($countryCode || ! empty($validated['postcode'])) {
            $validated['postcode'] = InternationalAddressHelper::resolveCountryAwarePostcode(
                $validated['postcode'] ?? null,
                $countryCode,
                null,
                false
            );
        }

        if (empty($validated['business_address']) && ! empty($validated['address_line_1'])) {
            $validated['business_address'] = InternationalAddressHelper::composeFullAddress(
                $validated['address_line_1'] ?? null,
                $validated['address_line_2'] ?? null,
                $validated['city'] ?? null,
                $validated['state_region'] ?? null,
                $validated['postcode'] ?? null,
                $validated['country_name'] ?? null
            );
        }

        if ($request->hasFile('trade_license_document')) {
            $validated['trade_license_document'] = $request->file('trade_license_document')->store('vendors/licenses', 'public');
        }

        $assignedStoreIds = $validated['assigned_store_ids'] ?? null;
        unset($validated['assigned_store_ids']);
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;

        $vendor = Vendor::create($validated);

        if (empty($vendor->vendor_code)) {
            $vendor->vendor_code = 'VND-' . str_pad((string) $vendor->id, 4, '0', STR_PAD_LEFT);
            $vendor->save();
        }

        if (is_array($assignedStoreIds) && ! empty($assignedStoreIds)) {
            Store::whereIn('id', $assignedStoreIds)->update([
                'vendor_id' => $vendor->id,
                'ownership_type' => 'vendor_store',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Vendor \"{$vendor->name}\" registered successfully.",
            'vendor' => $vendor,
        ], 201);
    }

    /**
     * Update Vendor.
     */
    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $validated = $request->validate([
            'vendor_code' => "nullable|string|max:50|unique:vendors,vendor_code,{$vendor->id}",
            'name' => 'required|string|max:190',
            'owner_name' => 'required|string|max:120',
            'business_type' => 'nullable|string|max:80',
            'phone_country_code' => 'nullable|string|max:10',
            'phone' => 'required|string|max:30',
            'alt_phone_country_code' => 'nullable|string|max:10',
            'alt_phone' => 'nullable|string|max:30',
            'email' => "nullable|email|max:120|unique:vendors,email,{$vendor->id}",
            'country_code' => 'nullable|string|max:4',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'state_region' => 'nullable|string|max:120',
            'postcode' => 'nullable|string|max:30',
            'business_address' => 'nullable|string',
            'trade_license_number' => 'nullable|string|max:100',
            'tax_vat_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'status' => 'required|in:pending,active,suspended,closed',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'required|in:percentage,fixed',
            'commission_rate' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:120',
            'service_ids' => 'nullable|array',
            'assigned_store_ids' => 'nullable|array',
            'assigned_store_ids.*' => 'exists:stores,id',
            'payout_method' => 'nullable|string|max:80',
            'payout_details' => 'nullable|string',
        ]);

        $validated['phone'] = InternationalAddressHelper::validatePhone($validated['phone'], 'phone') ?? $validated['phone'];
        if (! empty($validated['alt_phone'])) {
            $validated['alt_phone'] = InternationalAddressHelper::validatePhone($validated['alt_phone'], 'alt_phone');
        }

        $countryCode = ! empty($validated['country_code'])
            ? strtoupper(trim($validated['country_code']))
            : ($vendor->country_code ?: null);

        if ($countryCode) {
            $countryMeta = InternationalAddressHelper::findCountry($countryCode);
            $validated['country_code'] = $countryCode;
            $validated['country_name'] = $countryMeta['name'] ?? ($vendor->country_name ?: $countryCode);
        }

        if ($countryCode || ! empty($validated['postcode'])) {
            $validated['postcode'] = InternationalAddressHelper::resolveCountryAwarePostcode(
                $validated['postcode'] ?? null,
                $countryCode,
                $vendor->postcode,
                false
            );
        }

        if (empty($validated['business_address']) && ! empty($validated['address_line_1'])) {
            $validated['business_address'] = InternationalAddressHelper::composeFullAddress(
                $validated['address_line_1'] ?? null,
                $validated['address_line_2'] ?? null,
                $validated['city'] ?? null,
                $validated['state_region'] ?? null,
                $validated['postcode'] ?? null,
                $validated['country_name'] ?? null
            );
        }

        if ($request->hasFile('trade_license_document')) {
            if ($vendor->trade_license_document) {
                Storage::disk('public')->delete($vendor->trade_license_document);
            }
            $validated['trade_license_document'] = $request->file('trade_license_document')->store('vendors/licenses', 'public');
        }

        $assignedStoreIds = $validated['assigned_store_ids'] ?? null;
        unset($validated['assigned_store_ids']);

        $vendor->update($validated);

        if ($request->has('assigned_store_ids_present')) {
            $ids = is_array($assignedStoreIds) ? $assignedStoreIds : [];
            if (! empty($ids)) {
                Store::whereIn('id', $ids)->update([
                    'vendor_id' => $vendor->id,
                    'ownership_type' => 'vendor_store',
                ]);
            }
            Store::where('vendor_id', $vendor->id)
                ->when(! empty($ids), fn ($q) => $q->whereNotIn('id', $ids))
                ->update([
                    'vendor_id' => null,
                    'ownership_type' => 'my_store',
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Vendor \"{$vendor->name}\" updated successfully.",
            'vendor' => $vendor,
        ]);
    }

    /**
     * Toggle active / inactive (suspended) status for a vendor.
     */
    public function toggleStatus(Vendor $vendor): JsonResponse
    {
        $vendor->status = $vendor->status === 'active' ? 'suspended' : 'active';
        $vendor->save();

        $label = $vendor->status === 'active' ? 'Active' : 'Inactive';

        return response()->json([
            'success' => true,
            'status' => $vendor->status,
            'message' => "Vendor \"{$vendor->name}\" is now {$label}.",
        ]);
    }

    /**
     * Archive (soft-delete) Vendor safely without deleting historical store/booking records.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $name = $vendor->name;
        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => "Vendor \"{$name}\" archived successfully.",
        ]);
    }
}
