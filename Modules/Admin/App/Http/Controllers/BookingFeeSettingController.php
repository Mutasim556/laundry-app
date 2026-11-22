<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Database\Seeders\BookingFeeSettingSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Models\BookingFeeAuditLog;
use Modules\Admin\App\Models\BookingFeeSetting;

class BookingFeeSettingController extends Controller
{
    protected function ensureFeeSettingsExist(): void
    {
        if (BookingFeeSetting::allowed()->count() < count(BookingFeeSetting::ALLOWED_FEE_CODES)) {
            app(BookingFeeSettingSeeder::class)->run();
        }
    }

    protected function recordAudit(Request $request, string $feeCode, string $action, string $summary, array $changes = []): void
    {
        $admin = Auth::guard('admin')->user();

        BookingFeeAuditLog::create([
            'fee_code' => $feeCode,
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name ?? 'Admin',
            'action' => $action,
            'summary' => $summary,
            'changes' => $changes,
            'ip_address' => $request->ip(),
        ]);
    }

    public function index()
    {
        $this->ensureFeeSettingsExist();

        $fees = BookingFeeSetting::allowed()->get()->keyBy('fee_code');
        $serviceFee = $fees->get('service_fee');
        $deliveryFee = $fees->get('collection_delivery_fee');

        $auditLogs = BookingFeeAuditLog::orderByDesc('id')
            ->limit(25)
            ->get();

        return view('admin::booking-fees.index', compact('serviceFee', 'deliveryFee', 'auditLogs'));
    }

    public function updateServiceFee(Request $request): RedirectResponse
    {
        $this->ensureFeeSettingsExist();
        $setting = BookingFeeSetting::where('fee_code', 'service_fee')->firstOrFail();

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'fee_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'fee_value' => ['required', 'numeric', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['fee_type'] === 'percentage' && (float) $validated['fee_value'] > 100) {
            return back()->withErrors([
                'fee_value' => 'Percentage Service Fee cannot exceed 100%.',
            ])->withInput();
        }

        $oldValues = [
            'is_active' => $setting->is_active,
            'fee_type' => $setting->fee_type,
            'fee_value' => (float) $setting->fee_value,
        ];

        $setting->display_name = trim($validated['display_name']);
        $setting->fee_type = $validated['fee_type'];
        $setting->fee_value = round((float) $validated['fee_value'], 2);
        $setting->fee_value_minor = BookingFeeSetting::toMinor($setting->fee_value);
        $setting->is_active = $request->boolean('is_active');
        $setting->description = trim((string) ($validated['description'] ?? ''));
        $setting->updated_by = Auth::guard('admin')->id();
        $setting->save();

        $this->recordAudit($request, 'service_fee', 'updated', 'Updated Service Fee settings', [
            'before' => $oldValues,
            'after' => [
                'is_active' => $setting->is_active,
                'fee_type' => $setting->fee_type,
                'fee_value' => (float) $setting->fee_value,
            ],
        ]);

        return redirect()
            ->route('admin.booking-fees.index')
            ->with('success', 'Service Fee settings updated successfully.');
    }

    public function updateDeliveryFee(Request $request): RedirectResponse
    {
        $this->ensureFeeSettingsExist();
        $setting = BookingFeeSetting::where('fee_code', 'collection_delivery_fee')->firstOrFail();

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'fee_value' => ['required', 'numeric', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'is_free' => ['nullable', 'boolean'],
            'threshold_enabled' => ['nullable', 'boolean'],
            'threshold_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'threshold_after_discount' => ['nullable', 'boolean'],
        ]);

        $oldValues = [
            'is_active' => $setting->is_active,
            'is_free' => $setting->is_free,
            'fee_value' => (float) $setting->fee_value,
            'threshold_enabled' => $setting->threshold_enabled,
            'threshold_amount' => $setting->threshold_amount,
        ];

        $isActive = $request->boolean('is_active');
        $isFree = $request->boolean('is_free');
        $thresholdEnabled = $request->boolean('threshold_enabled');
        $thresholdAmount = round((float) ($validated['threshold_amount'] ?? 0), 2);

        $setting->display_name = trim($validated['display_name']);
        $setting->fee_type = 'fixed';
        $setting->fee_value = round((float) $validated['fee_value'], 2);
        $setting->fee_value_minor = BookingFeeSetting::toMinor($setting->fee_value);
        $setting->is_active = $isActive;
        $setting->is_free = $isFree;
        $setting->threshold_enabled = $thresholdEnabled && $thresholdAmount > 0;
        $setting->threshold_amount_minor = BookingFeeSetting::toMinor($thresholdAmount);
        $setting->threshold_after_discount = $request->boolean('threshold_after_discount', true);
        $setting->description = trim((string) ($validated['description'] ?? ''));
        $setting->updated_by = Auth::guard('admin')->id();
        $setting->save();

        $this->recordAudit($request, 'collection_delivery_fee', 'updated', 'Updated Collection & Delivery Fee settings', [
            'before' => $oldValues,
            'after' => [
                'is_active' => $setting->is_active,
                'is_free' => $setting->is_free,
                'fee_value' => (float) $setting->fee_value,
                'threshold_enabled' => $setting->threshold_enabled,
                'threshold_amount' => $setting->threshold_amount,
            ],
        ]);

        return redirect()
            ->route('admin.booking-fees.index')
            ->with('success', 'Collection & Delivery Fee settings updated successfully.');
    }
}
