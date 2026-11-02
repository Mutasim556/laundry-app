<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\District;
use Modules\Admin\Models\Division;
use Modules\Admin\Models\LocalArea;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
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

        return view('admin::stores.index', compact(
            'vendors',
            'admins',
            'services',
            'divisions'
        ));
    }

    /**
     * Return JSON data for Stores DataTable.
     */
    public function data(): JsonResponse
    {
        $stores = Store::with(['vendor', 'admin', 'division', 'district', 'upazila', 'localArea'])
            ->latest('id')
            ->get();

        $data = $stores->map(function (Store $store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'bn_name' => $store->bn_name ?? '-',
                'slug' => $store->slug,
                'ownership_type' => $store->ownership_type,
                'vendor_id' => $store->vendor_id,
                'vendor_name' => $store->vendor ? $store->vendor->name : ($store->ownership_type === 'my_store' ? 'My Business' : '-'),
                'admin_id' => $store->admin_id,
                'admin_name' => $store->admin ? $store->admin->name : '-',
                'status' => $store->status,
                'store_type' => $store->store_type,
                'logo_url' => $store->logo_url,
                'cover_image_url' => $store->cover_image_url,
                'phone' => $store->phone,
                'alt_phone' => $store->alt_phone ?? '',
                'email' => $store->email ?? '',
                'contact_person' => $store->contact_person ?? '',
                'holding_no' => $store->holding_no ?? '',
                'road_no' => $store->road_no ?? '',
                'nearby_landmark' => $store->nearby_landmark ?? '',
                'full_address' => $store->full_address,
                'division_id' => $store->division_id,
                'division_name' => $store->division?->name ?? '',
                'district_id' => $store->district_id,
                'district_name' => $store->district?->name ?? '',
                'upazila_id' => $store->upazila_id,
                'upazila_name' => $store->upazila?->name ?? '',
                'local_area_id' => $store->local_area_id,
                'area_name' => $store->localArea?->name ?? ($store->area_name ?? ''),
                'postcode' => $store->postcode ?? '',
                'pricing_model' => $store->pricing_model,
                'min_order_amount' => (float) $store->min_order_amount,
                'has_delivery' => (bool) $store->has_delivery,
                'delivery_fee' => (float) $store->delivery_fee,
                'free_delivery_threshold' => $store->free_delivery_threshold ? (float) $store->free_delivery_threshold : null,
                'turnaround_time' => $store->turnaround_time ?? '',
                'is_accepting_orders' => (bool) $store->is_accepting_orders,
                'service_ids' => $store->service_ids ?? [],
                'service_area_ids' => $store->service_area_ids ?? [],
                'service_areas_text' => $store->service_areas_text ?? '',
                'opening_hours' => $store->opening_hours ?? [],
                'description' => $store->description ?? '',
                'verification_notes' => $store->verification_notes ?? '',
                'commission_type' => $store->commission_type ?? '',
                'commission_rate' => $store->commission_rate ? (float) $store->commission_rate : null,
                'created_at' => $store->created_at ? $store->created_at->format('M d, Y') : '-',
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
            'store_type' => 'required|in:laundry,dry_cleaning,both',
            'contact_person' => 'nullable|string|max:120',
            'phone' => 'required|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'holding_no' => 'nullable|string|max:100',
            'road_no' => 'nullable|string|max:100',
            'nearby_landmark' => 'nullable|string|max:190',
            'full_address' => 'required|string',
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'upazila_id' => 'required|exists:upazilas,id',
            'local_area_id' => 'nullable|exists:local_areas,id',
            'area_name' => 'nullable|string|max:190',
            'postcode' => 'nullable|string|max:20',
            'pricing_model' => 'nullable|string|max:50',
            'min_order_amount' => 'nullable|numeric|min:0',
            'has_delivery' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'free_delivery_threshold' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|string|max:100',
            'is_accepting_orders' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_area_ids' => 'nullable|array',
            'service_areas_text' => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'description' => 'nullable|string',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'nullable|string|max:50',
            'commission_rate' => 'nullable|numeric|min:0',
            'logo' => 'nullable|image|max:2048',
            'cover_image' => 'nullable|image|max:3072',
        ]);

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
            'store_type' => 'required|in:laundry,dry_cleaning,both',
            'contact_person' => 'nullable|string|max:120',
            'phone' => 'required|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'holding_no' => 'nullable|string|max:100',
            'road_no' => 'nullable|string|max:100',
            'nearby_landmark' => 'nullable|string|max:190',
            'full_address' => 'required|string',
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'upazila_id' => 'required|exists:upazilas,id',
            'local_area_id' => 'nullable|exists:local_areas,id',
            'area_name' => 'nullable|string|max:190',
            'postcode' => 'nullable|string|max:20',
            'pricing_model' => 'nullable|string|max:50',
            'min_order_amount' => 'nullable|numeric|min:0',
            'has_delivery' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'free_delivery_threshold' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|string|max:100',
            'is_accepting_orders' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_area_ids' => 'nullable|array',
            'service_areas_text' => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'description' => 'nullable|string',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'nullable|string|max:50',
            'commission_rate' => 'nullable|numeric|min:0',
            'logo' => 'nullable|image|max:2048',
            'cover_image' => 'nullable|image|max:3072',
            'remove_logo' => 'nullable|boolean',
            'remove_cover' => 'nullable|boolean',
        ]);

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
     * Delete Store.
     */
    public function destroy(Store $store): JsonResponse
    {
        $name = $store->name;
        $store->delete();

        return response()->json([
            'success' => true,
            'message' => "Store \"{$name}\" deleted successfully.",
        ]);
    }
}
