<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Vendor;

class VendorController extends Controller
{
    /**
     * Display Vendors management index page.
     */
    public function index()
    {
        return view('admin::vendors.index');
    }

    /**
     * Return JSON data for Vendors DataTable.
     */
    public function data(): JsonResponse
    {
        $vendors = Vendor::withCount('stores')->latest('id')->get();

        $data = $vendors->map(function (Vendor $vendor) {
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'owner_name' => $vendor->owner_name,
                'phone' => $vendor->phone,
                'alt_phone' => $vendor->alt_phone ?? '',
                'email' => $vendor->email ?? '',
                'business_address' => $vendor->business_address ?? '',
                'trade_license_number' => $vendor->trade_license_number ?? '',
                'trade_license_document_url' => $vendor->trade_license_document ? asset('storage/' . $vendor->trade_license_document) : null,
                'status' => $vendor->status,
                'verification_notes' => $vendor->verification_notes ?? '',
                'commission_type' => $vendor->commission_type,
                'commission_rate' => (float) $vendor->commission_rate,
                'payout_method' => $vendor->payout_method ?? '',
                'payout_details' => $vendor->payout_details ?? '',
                'stores_count' => $vendor->stores_count,
                'created_at' => $vendor->created_at ? $vendor->created_at->format('M d, Y') : '-',
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
            'name' => 'required|string|max:190',
            'owner_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120|unique:vendors,email',
            'business_address' => 'nullable|string',
            'trade_license_number' => 'nullable|string|max:100',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'status' => 'required|in:pending,active,suspended,closed',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'required|in:percentage,fixed',
            'commission_rate' => 'nullable|numeric|min:0',
            'payout_method' => 'nullable|string|max:80',
            'payout_details' => 'nullable|string',
        ]);

        if ($request->hasFile('trade_license_document')) {
            $validated['trade_license_document'] = $request->file('trade_license_document')->store('vendors/licenses', 'public');
        }

        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;

        $vendor = Vendor::create($validated);

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
            'name' => 'required|string|max:190',
            'owner_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => "nullable|email|max:120|unique:vendors,email,{$vendor->id}",
            'business_address' => 'nullable|string',
            'trade_license_number' => 'nullable|string|max:100',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'status' => 'required|in:pending,active,suspended,closed',
            'verification_notes' => 'nullable|string',
            'commission_type' => 'required|in:percentage,fixed',
            'commission_rate' => 'nullable|numeric|min:0',
            'payout_method' => 'nullable|string|max:80',
            'payout_details' => 'nullable|string',
        ]);

        if ($request->hasFile('trade_license_document')) {
            if ($vendor->trade_license_document) {
                Storage::disk('public')->delete($vendor->trade_license_document);
            }
            $validated['trade_license_document'] = $request->file('trade_license_document')->store('vendors/licenses', 'public');
        }

        $vendor->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Vendor \"{$vendor->name}\" updated successfully.",
            'vendor' => $vendor,
        ]);
    }

    /**
     * Delete Vendor.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $name = $vendor->name;
        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => "Vendor \"{$name}\" deleted successfully.",
        ]);
    }
}
