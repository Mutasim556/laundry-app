<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingInvoice;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Setting;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingInvoiceService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function issueOrGetCurrentInvoice(Booking $booking, ?int $adminId = null): BookingInvoice
    {
        return DB::transaction(function () use ($booking, $adminId) {
            /** @var Booking $locked */
            $locked = Booking::query()
                ->with(['currentStoreAssignment', 'operationalItems.category', 'operationalItems.item', 'operationalItems.service'])
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isInvoiceEligible($this->currencyService)) {
                throw new HttpException(403, 'Invoice is only available after the Finalized workflow stage is completed and operational pricing is complete.');
            }

            $pricing = $locked->getOperationalPricingSummary($this->currencyService, false);
            $categoryGroups = collect($locked->getActiveOperationalCategoryGroups());

            $itemsSnapshot = $categoryGroups->map(function (array $group) use ($locked) {
                return [
                    'category_name' => $group['category_name'],
                    'items' => collect($group['items'])->map(function ($line) use ($locked) {
                        $qtyDisplay = $line->pricing_mode === 'weight'
                            ? number_format((float) $line->weight_kg, 2) . ' kg'
                            : (string) ((int) $line->quantity);
                        $unitMinor = (int) ($line->actual_unit_price_minor ?? $line->catalog_unit_price_minor ?? 0);
                        $lineMinor = (int) $line->computeLineTotalMinor();
                        $curr = $line->currency ?: $locked->currency;

                        return [
                            'service_name' => $line->service_name,
                            'item_name' => $line->item_name,
                            'pricing_mode' => $line->pricing_mode,
                            'qty_display' => $qtyDisplay,
                            'unit_price_minor' => $unitMinor,
                            'unit_price_formatted' => $this->currencyService->formatMinor($unitMinor, $curr)
                                . ($line->pricing_mode === 'weight' ? ' / kg' : ''),
                            'line_total_minor' => $lineMinor,
                            'line_total_formatted' => $this->currencyService->formatMinor($lineMinor, $curr),
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            $address = is_array($locked->address_snapshot) ? $locked->address_snapshot : [];
            $schedule = is_array($locked->schedule_snapshot) ? $locked->schedule_snapshot : [];
            $storeAssign = $locked->currentStoreAssignment;

            $customerSnapshot = [
                'name' => $locked->customer_name,
                'phone' => $locked->customer_phone ?: null,
                'email' => $locked->customer_email ?: null,
                'address' => $address['full_address'] ?? $address['address_line'] ?? null,
                'postcode' => $address['postcode'] ?? null,
                'hotel_name' => $address['hotel_name'] ?? null,
                'room_number' => $address['room_number'] ?? null,
                'collection_date' => $schedule['collection_date'] ?? $schedule['pickup_date'] ?? null,
                'collection_slot' => $schedule['collection_slot'] ?? $schedule['pickup_slot'] ?? null,
                'delivery_date' => $schedule['delivery_date'] ?? null,
                'delivery_slot' => $schedule['delivery_slot'] ?? null,
                'assigned_store_name' => $storeAssign?->store_name_snapshot,
                'assigned_store_address' => $storeAssign?->store_address_snapshot,
                'assigned_store_postcode' => $storeAssign?->store_postcode_snapshot,
                'assigned_store_phone' => $storeAssign?->store_phone_snapshot,
            ];

            $businessSnapshot = [
                'name' => (string) (Setting::get('site_name') ?: config('app.name', 'Laundry & Dry Cleaning')),
                'logo_url' => Setting::getLogoUrl(),
                'phone' => Setting::get('contact_phone') ?: Setting::get('site_phone') ?: $storeAssign?->store_phone_snapshot,
                'email' => Setting::get('contact_email') ?: Setting::get('site_email'),
                'address' => Setting::get('contact_address') ?: Setting::get('site_address') ?: $storeAssign?->store_address_snapshot,
                'vat_number' => Setting::get('vat_number') ?: Setting::get('tax_registration_number'),
            ];

            $financialPayload = [
                'currency' => $locked->currency,
                'items_subtotal_minor' => (int) $pricing['items_subtotal_minor'],
                'service_fee_minor' => (int) $pricing['service_fee_minor'],
                'delivery_fee_minor' => (int) $pricing['delivery_fee_minor'],
                'tip_minor' => (int) $pricing['tip_minor'],
                'tax_minor' => (int) $pricing['tax_minor'],
                'discount_minor' => (int) $pricing['discount_minor'],
                'final_total_minor' => (int) $pricing['final_total_minor'],
                'payment_gateway' => strtoupper((string) $locked->payment_gateway),
                'payment_status' => $pricing['payment_status_label'],
                'items_snapshot' => $itemsSnapshot,
            ];

            $snapshotHash = hash('sha256', json_encode($financialPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            /** @var BookingInvoice|null $current */
            $current = BookingInvoice::query()
                ->where('booking_id', $locked->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($current && hash_equals((string) $current->snapshot_hash, $snapshotHash)) {
                return $current;
            }

            $revisionNumber = $current ? ((int) $current->revision_number + 1) : 1;
            if ($current) {
                $current->update(['is_current' => false]);
                $baseNumber = preg_replace('/-R\d+$/', '', (string) $current->invoice_number);
                $invoiceNumber = "{$baseNumber}-R{$revisionNumber}";
            } else {
                $invoiceNumber = $this->generateUniqueInvoiceNumber();
            }

            return BookingInvoice::create([
                'booking_id' => $locked->id,
                'invoice_number' => $invoiceNumber,
                'revision_number' => $revisionNumber,
                'is_current' => true,
                'currency' => $locked->currency,
                'items_subtotal_minor' => (int) $pricing['items_subtotal_minor'],
                'service_fee_minor' => (int) $pricing['service_fee_minor'],
                'delivery_fee_minor' => (int) $pricing['delivery_fee_minor'],
                'tip_minor' => (int) $pricing['tip_minor'],
                'tax_minor' => (int) $pricing['tax_minor'],
                'discount_minor' => (int) $pricing['discount_minor'],
                'final_total_minor' => (int) $pricing['final_total_minor'],
                'payment_gateway' => strtoupper((string) $locked->payment_gateway),
                'payment_status' => $pricing['payment_status_label'],
                'pricing_status_label' => $pricing['pricing_status_label'],
                'business_snapshot' => $businessSnapshot,
                'customer_snapshot' => $customerSnapshot,
                'items_snapshot' => $itemsSnapshot,
                'snapshot_hash' => $snapshotHash,
                'issued_by_admin_id' => $adminId,
                'issued_at' => now(),
            ]);
        });
    }

    protected function generateUniqueInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "INV-{$year}-";

        $latest = BookingInvoice::query()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('invoice_number');

        $nextSeq = 1;
        if ($latest && preg_match('/^INV-\d{4}-(\d+)/', (string) $latest, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }

        do {
            $candidate = $prefix . str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
            $nextSeq++;
        } while (BookingInvoice::where('invoice_number', $candidate)->exists());

        return $candidate;
    }
}
