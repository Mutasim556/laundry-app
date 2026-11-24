<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Support\CountryDialCodes;
use App\Support\InternationalAddressHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\District;
use Modules\Admin\Models\Division;
use Modules\Admin\Models\LocalArea;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;
use Modules\Admin\Models\Upazila;
use Modules\Admin\Models\Vendor;

class StoreController extends Controller
{
    /**
     * Display the stores index page.
     */
    public function index()
    {
        $vendors = Vendor::orderBy('name')->get();
        $admins = Admin::orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $divisions = Division::where('is_active', true)->orderBy('name')->get();
        $countries = CountryDialCodes::all();
        $globalSettings = app(CurrencyService::class)->getGlobalSettings();
        $addressConfigMap = InternationalAddressHelper::addressConfigMap();

        $allStores = Store::select('id', 'status', 'vendor_id', 'country_code', 'country_name', 'city')->get();
        $byCountry = $allStores
            ->filter(fn (Store $s) => filled($s->country_name))
            ->groupBy('country_name')
            ->map->count()
            ->sortDesc();

        $stats = [
            'total' => $allStores->count(),
            'active' => $allStores->where('status', 'active')->count(),
            'inactive' => $allStores->where('status', '!=', 'active')->count(),
            'linked_vendors' => $allStores->whereNotNull('vendor_id')->pluck('vendor_id')->unique()->count(),
            'countries_count' => $byCountry->count(),
            'top_country' => $byCountry->keys()->first() ?? 'None',
            'top_country_count' => $byCountry->first() ?? 0,
        ];

        $filterCountries = $allStores->pluck('country_name')->filter()->unique()->sort()->values();
        $filterCities = $allStores->pluck('city')->filter()->unique()->sort()->values();

        return view('admin::stores.index', compact(
            'vendors',
            'admins',
            'services',
            'divisions',
            'countries',
            'globalSettings',
            'addressConfigMap',
            'stats',
            'filterCountries',
            'filterCities'
        ));
    }

    /**
     * Return JSON data for Stores DataTable.
     */
    public function data(): JsonResponse
    {
        $globalSettings = app(CurrencyService::class)->getGlobalSettings();
        $currencySymbol = $globalSettings['currency_symbol'] ?? '£';

        $stores = Store::with(['vendor', 'admin', 'division', 'district', 'upazila', 'localArea'])
            ->latest('id')
            ->get();

        $data = $stores->map(function (Store $store) use ($currencySymbol) {
            $city = $store->city ?: ($store->district?->name ?? '');
            $countryName = $store->country_name ?: '';
            $displayCode = $store->store_code ?: ('STR-' . str_pad((string) $store->id, 4, '0', STR_PAD_LEFT));
            $phoneFull = trim(($store->phone_country_code ? $store->phone_country_code . ' ' : '') . $store->phone);

            return [
                'id' => $store->id,
                'store_code' => $displayCode,
                'name' => $store->name,
                'bn_name' => $store->bn_name ?? '',
                'slug' => $store->slug,
                'ownership_type' => $store->ownership_type,
                'vendor_id' => $store->vendor_id,
                'vendor_name' => $store->vendor ? $store->vendor->name : ($store->ownership_type === 'my_store' ? 'In-House Store' : '—'),
                'admin_id' => $store->admin_id,
                'admin_name' => $store->admin ? $store->admin->name : '—',
                'status' => $store->status,
                'store_type' => $store->store_type,
                'logo_url' => $store->logo_url,
                'cover_image_url' => $store->cover_image_url,
                'phone_country_code' => $store->phone_country_code ?? '',
                'phone' => $store->phone,
                'phone_display' => $phoneFull,
                'alt_phone_country_code' => $store->alt_phone_country_code ?? '',
                'alt_phone' => $store->alt_phone ?? '',
                'email' => $store->email ?? '',
                'website' => $store->website ?? '',
                'contact_person' => $store->contact_person ?? '',
                'country_code' => $store->country_code ?? '',
                'country_name' => $countryName,
                'address_line_1' => $store->address_line_1 ?? '',
                'address_line_2' => $store->address_line_2 ?? '',
                'city' => $city,
                'state_region' => $store->state_region ?? '',
                'holding_no' => $store->holding_no ?? '',
                'road_no' => $store->road_no ?? '',
                'nearby_landmark' => $store->nearby_landmark ?? '',
                'full_address' => $store->full_address ?? '',
                'division_id' => $store->division_id,
                'division_name' => $store->division?->name ?? '',
                'district_id' => $store->district_id,
                'district_name' => $store->district?->name ?? '',
                'upazila_id' => $store->upazila_id,
                'upazila_name' => $store->upazila?->name ?? '',
                'local_area_id' => $store->local_area_id,
                'area_name' => $store->localArea?->name ?? ($store->area_name ?? ''),
                'postcode' => $store->postcode ?? '',
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
                'postcode_in_uk_addresses' => filled($store->postcode)
                    ? UkAddress::query()
                        ->where('is_active', true)
                        ->where('postcode_compact', UkAddress::compactPostcode((string) $store->postcode))
                        ->exists()
                    : true,
                'pricing_model' => $store->pricing_model,
                'min_order_amount' => (float) $store->min_order_amount,
                'has_delivery' => (bool) $store->has_delivery,
                'delivery_fee' => (float) $store->delivery_fee,
                'free_delivery_threshold' => $store->free_delivery_threshold ? (float) $store->free_delivery_threshold : null,
                'turnaround_time' => $store->turnaround_time ?? '',
                'capacity' => $store->capacity,
                'is_accepting_orders' => (bool) $store->is_accepting_orders,
                'service_ids' => $store->service_ids ?? [],
                'service_area_ids' => $store->service_area_ids ?? [],
                'service_areas_text' => $store->service_areas_text ?? '',
                'opening_hours' => $store->opening_hours ?? [],
                'description' => $store->description ?? '',
                'verification_notes' => $store->verification_notes ?? '',
                'commission_type' => $store->commission_type ?? '',
                'commission_rate' => $store->commission_rate ? (float) $store->commission_rate : null,
                'currency_symbol' => $currencySymbol,
                'created_at' => $store->created_at ? $store->created_at->format('M d, Y') : 'Not provided',
                'updated_at' => $store->updated_at ? $store->updated_at->format('M d, Y') : 'Not provided',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Check or generate unique slug for Store.
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $name = trim($request->query('name', ''));
        $ignoreId = $request->query('ignore_id');

        if (empty($name)) {
            return response()->json(['slug' => '']);
        }

        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Store::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return response()->json(['slug' => $slug]);
    }

    /**
     * Get districts by division.
     */
    public function getDistricts(Request $request): JsonResponse
    {
        $divisionId = $request->query('division_id');
        $districts = District::where('division_id', $divisionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bn_name']);

        return response()->json($districts);
    }

    /**
     * Get upazilas/thanas by district.
     */
    public function getUpazilas(Request $request): JsonResponse
    {
        $districtId = $request->query('district_id');
        $upazilas = Upazila::where('district_id', $districtId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bn_name']);

        return response()->json($upazilas);
    }

    /**
     * Get local areas by upazila.
     */
    public function getLocalAreas(Request $request): JsonResponse
    {
        $upazilaId = $request->query('upazila_id');
        $areas = LocalArea::where('upazila_id', $upazilaId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bn_name', 'postcode']);

        return response()->json($areas);
    }

    /**
     * Store a newly created Store.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ownership_type' => 'required|in:my_store,vendor_store',
            'vendor_id' => 'nullable|required_if:ownership_type,vendor_store|exists:vendors,id',
            'admin_id' => 'nullable|exists:admins,id',
            'status' => 'required|in:pending,active,suspended,closed',
            'name' => 'required|string|max:190',
            'bn_name' => 'nullable|string|max:190',
            'slug' => 'nullable|string|max:190|unique:stores,slug',
            'store_code' => 'nullable|string|max:50|unique:stores,store_code',
            'store_type' => 'required|in:laundry,dry_cleaning,both',
            'contact_person' => 'nullable|string|max:120',
            'phone_country_code' => 'nullable|string|max:10',
            'phone' => 'required|string|max:30',
            'alt_phone_country_code' => 'nullable|string|max:10',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'website' => 'nullable|url|max:255',
            'holding_no' => 'nullable|string|max:100',
            'road_no' => 'nullable|string|max:100',
            'nearby_landmark' => 'nullable|string|max:190',
            'full_address' => 'nullable|string|required_without:address_line_1',
            'country_code' => 'nullable|string|max:4',
            'address_line_1' => 'nullable|string|max:255|required_without:full_address',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120|required_with:address_line_1',
            'state_region' => 'nullable|string|max:120',
            'division_id' => 'nullable|exists:divisions,id',
            'district_id' => 'nullable|exists:districts,id',
            'upazila_id' => 'nullable|exists:upazilas,id',
            'local_area_id' => 'nullable|exists:local_areas,id',
            'area_name' => 'nullable|string|max:190',
            'postcode' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'pricing_model' => 'nullable|string|max:50',
            'min_order_amount' => 'nullable|numeric|min:0',
            'has_delivery' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'free_delivery_threshold' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:100000',
            'is_accepting_orders' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_area_ids' => 'nullable|array',
            'service_areas_text' => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'description' => 'nullable|string',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'nullable|string|max:50',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'logo' => 'nullable|image|max:2048',
            'cover_image' => 'nullable|image|max:3072',
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

        $validated['postcode'] = InternationalAddressHelper::resolveCountryAwarePostcode(
            $validated['postcode'] ?? null,
            $countryCode,
            null,
            $countryCode === 'GB'
        );

        if (empty($validated['full_address'])) {
            $validated['full_address'] = InternationalAddressHelper::composeFullAddress(
                $validated['address_line_1'] ?? null,
                $validated['address_line_2'] ?? null,
                $validated['city'] ?? null,
                $validated['state_region'] ?? null,
                $validated['postcode'] ?? null,
                $validated['country_name'] ?? null
            );
        }

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Store::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['has_delivery'] = $request->boolean('has_delivery', true);
        $validated['is_accepting_orders'] = $request->boolean('is_accepting_orders', true);
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;
        $validated['delivery_fee'] = $validated['delivery_fee'] ?? 0;

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('stores/logos', 'public');
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('stores/covers', 'public');
        }

        $store = Store::create($validated);

        if (empty($store->store_code)) {
            $store->store_code = 'STR-' . str_pad((string) $store->id, 4, '0', STR_PAD_LEFT);
            $store->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Store \"{$store->name}\" created successfully.",
            'store' => $store,
        ], 201);
    }

    /**
     * Update an existing Store.
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'ownership_type' => 'required|in:my_store,vendor_store',
            'vendor_id' => 'nullable|required_if:ownership_type,vendor_store|exists:vendors,id',
            'admin_id' => 'nullable|exists:admins,id',
            'status' => 'required|in:pending,active,suspended,closed',
            'name' => 'required|string|max:190',
            'bn_name' => 'nullable|string|max:190',
            'slug' => "nullable|string|max:190|unique:stores,slug,{$store->id}",
            'store_code' => "nullable|string|max:50|unique:stores,store_code,{$store->id}",
            'store_type' => 'required|in:laundry,dry_cleaning,both',
            'contact_person' => 'nullable|string|max:120',
            'phone_country_code' => 'nullable|string|max:10',
            'phone' => 'required|string|max:30',
            'alt_phone_country_code' => 'nullable|string|max:10',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'website' => 'nullable|url|max:255',
            'holding_no' => 'nullable|string|max:100',
            'road_no' => 'nullable|string|max:100',
            'nearby_landmark' => 'nullable|string|max:190',
            'full_address' => 'nullable|string|required_without:address_line_1',
            'country_code' => 'nullable|string|max:4',
            'address_line_1' => 'nullable|string|max:255|required_without:full_address',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120|required_with:address_line_1',
            'state_region' => 'nullable|string|max:120',
            'division_id' => 'nullable|exists:divisions,id',
            'district_id' => 'nullable|exists:districts,id',
            'upazila_id' => 'nullable|exists:upazilas,id',
            'local_area_id' => 'nullable|exists:local_areas,id',
            'area_name' => 'nullable|string|max:190',
            'postcode' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'pricing_model' => 'nullable|string|max:50',
            'min_order_amount' => 'nullable|numeric|min:0',
            'has_delivery' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'free_delivery_threshold' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:100000',
            'is_accepting_orders' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_area_ids' => 'nullable|array',
            'service_areas_text' => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'description' => 'nullable|string',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'nullable|string|max:50',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'logo' => 'nullable|image|max:2048',
            'cover_image' => 'nullable|image|max:3072',
            'remove_logo' => 'nullable|boolean',
            'remove_cover' => 'nullable|boolean',
        ]);

        $validated['phone'] = InternationalAddressHelper::validatePhone($validated['phone'], 'phone') ?? $validated['phone'];
        if (! empty($validated['alt_phone'])) {
            $validated['alt_phone'] = InternationalAddressHelper::validatePhone($validated['alt_phone'], 'alt_phone');
        }

        $countryCode = ! empty($validated['country_code'])
            ? strtoupper(trim($validated['country_code']))
            : ($store->country_code ?: null);

        if ($countryCode) {
            $countryMeta = InternationalAddressHelper::findCountry($countryCode);
            $validated['country_code'] = $countryCode;
            $validated['country_name'] = $countryMeta['name'] ?? ($store->country_name ?: $countryCode);
        }

        $validated['postcode'] = InternationalAddressHelper::resolveCountryAwarePostcode(
            $validated['postcode'] ?? null,
            $countryCode,
            $store->postcode,
            $countryCode === 'GB'
        );

        if (empty($validated['full_address'])) {
            $validated['full_address'] = InternationalAddressHelper::composeFullAddress(
                $validated['address_line_1'] ?? $store->address_line_1,
                $validated['address_line_2'] ?? $store->address_line_2,
                $validated['city'] ?? $store->city,
                $validated['state_region'] ?? $store->state_region,
                $validated['postcode'] ?? null,
                $validated['country_name'] ?? $store->country_name
            );
        }

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Store::where('slug', $slug)->where('id', '!=', $store->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['has_delivery'] = $request->boolean('has_delivery', true);
        $validated['is_accepting_orders'] = $request->boolean('is_accepting_orders', true);
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;
        $validated['delivery_fee'] = $validated['delivery_fee'] ?? 0;

        if ($request->boolean('remove_logo') && $store->logo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($store->logo);
            $validated['logo'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($store->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($store->logo);
            }
            $validated['logo'] = $request->file('logo')->store('stores/logos', 'public');
        }

        if ($request->boolean('remove_cover') && $store->cover_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($store->cover_image);
            $validated['cover_image'] = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($store->cover_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($store->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('stores/covers', 'public');
        }

        if ($validated['ownership_type'] === 'my_store') {
            $validated['vendor_id'] = null;
        }

        $store->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Store \"{$store->name}\" updated successfully.",
            'store' => $store,
        ]);
    }

    /**
     * Toggle accepting orders or status.
     */
    public function toggleAcceptingOrders(Store $store): JsonResponse
    {
        $store->is_accepting_orders = !$store->is_accepting_orders;
        $store->save();

        return response()->json([
            'success' => true,
            'is_accepting_orders' => $store->is_accepting_orders,
            'message' => "Order acceptance set to " . ($store->is_accepting_orders ? 'Active' : 'Unavailable') . ".",
        ]);
    }

    /**
     * Toggle active / inactive (suspended) status for a store without affecting historical records.
     */
    public function toggleStatus(Store $store): JsonResponse
    {
        $store->status = $store->status === 'active' ? 'suspended' : 'active';
        $store->save();

        $label = $store->status === 'active' ? 'Active' : 'Inactive';

        return response()->json([
            'success' => true,
            'status' => $store->status,
            'message' => "Store \"{$store->name}\" is now {$label}.",
        ]);
    }

    /**
     * Archive (soft-delete) Store safely without deleting historical bookings.
     */
    public function destroy(Store $store): JsonResponse
    {
        $name = $store->name;
        $store->delete();

        return response()->json([
            'success' => true,
            'message' => "Store \"{$name}\" archived successfully.",
        ]);
    }

    /**
     * Return distinct, normalized UK postcodes from uk_addresses for Select2.
     */
    public function ukPostcodes(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 30;

        $query = UkAddress::query()
            ->where('is_active', true)
            ->whereNotNull('postcode_compact')
            ->where('postcode_compact', '!=', '')
            ->whereNotNull('postcode')
            ->whereRaw("TRIM(postcode) != ''");

        if ($search !== '') {
            $compactSearch = UkAddress::compactPostcode($search);
            $normalizedSearch = UkAddress::normalizePostcode($search)
                ?? strtoupper((string) preg_replace('/\s+/', ' ', $search));

            $query->where(function ($subQuery) use ($compactSearch, $normalizedSearch) {
                if ($compactSearch !== '') {
                    $subQuery->where('postcode_compact', 'like', "%{$compactSearch}%");
                }
                if ($normalizedSearch !== '') {
                    $subQuery->orWhere('postcode', 'like', "%{$normalizedSearch}%");
                }
            });
        }

        $distinctPostcodes = $query
            ->selectRaw('postcode_compact, MIN(postcode) as postcode')
            ->groupBy('postcode_compact')
            ->get()
            ->map(function ($row): ?string {
                $raw = trim((string) $row->postcode);
                if ($raw === '') {
                    return null;
                }

                return UkAddress::normalizePostcode($raw)
                    ?? strtoupper((string) preg_replace('/\s+/', ' ', $raw));
            })
            ->filter()
            ->unique()
            ->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
            ->values();

        $total = $distinctPostcodes->count();
        $offset = ($page - 1) * $perPage;
        $results = $distinctPostcodes
            ->slice($offset, $perPage)
            ->map(fn (string $postcode) => [
                'id' => $postcode,
                'text' => $postcode,
            ])
            ->values();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($offset + $perPage) < $total,
            ],
        ]);
    }

    /**
     * Normalize and verify that a submitted store postcode exists in uk_addresses.
     *
     * @throws ValidationException
     */
    protected function resolveValidatedUkPostcode(?string $rawPostcode, ?Store $existingStore = null): ?string
    {
        $trimmed = trim((string) ($rawPostcode ?? ''));
        if ($trimmed === '') {
            return null;
        }

        $compact = UkAddress::compactPostcode($trimmed);
        $match = UkAddress::query()
            ->where('is_active', true)
            ->where('postcode_compact', $compact)
            ->first();

        if ($match) {
            return UkAddress::normalizePostcode($match->postcode)
                ?? strtoupper((string) preg_replace('/\s+/', ' ', trim((string) $match->postcode)));
        }

        // Allow an existing store's legacy postcode to remain unchanged when editing other fields.
        if ($existingStore && filled($existingStore->postcode)) {
            $existingCompact = UkAddress::compactPostcode((string) $existingStore->postcode);
            if ($compact !== '' && $compact === $existingCompact) {
                return $existingStore->postcode;
            }
        }

        throw ValidationException::withMessages([
            'postcode' => ['Please select a valid postcode from the UK address database.'],
        ]);
    }
}
