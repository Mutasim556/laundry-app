<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LaundryTag;
use App\Models\LaundryTagAllocation;
use App\Models\LaundryTagBatch;
use App\Models\LaundryTagEvent;
use App\Models\LaundryTagPrintJob;
use App\Models\TagPrinterProfile;
use App\Services\LaundryTagQrService;
use App\Services\LaundryTagService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Setting;
use Modules\Admin\Models\Store;

class LaundryTagController extends Controller
{
    public function __construct(
        private readonly LaundryTagService $tagService,
        private readonly LaundryTagQrService $qrService,
    ) {}

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        return $admin;
    }

    /**
     * 1. Tag Inventory page & AJAX table endpoint.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->tagService->ensureDefaultPrinterProfiles($this->admin()->id);

        $filters = $request->only([
            'search',
            'status',
            'batch_id',
            'delivery_person_id',
            'print_status',
            'date_from',
            'date_to',
            'sort',
        ]);

        $tags = $this->tagService->buildFilteredTagsQuery($filters)
            ->paginate(24)
            ->withQueryString();

        $summary = $this->tagService->getInventorySummary();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'table_html' => view('admin::tags.partials.inventory-table', compact('tags'))->render(),
            ]);
        }

        $batches = LaundryTagBatch::orderByDesc('id')->limit(60)->get();
        $deliveryPersons = DeliveryPerson::orderBy('full_name')->get();
        $profiles = TagPrinterProfile::orderByDesc('is_default')->orderBy('id')->get();
        $statusLabels = LaundryTag::STATUS_LABELS;

        return view('admin::tags.index', compact(
            'tags',
            'summary',
            'batches',
            'deliveryPersons',
            'profiles',
            'statusLabels',
            'filters'
        ));
    }

    /**
     * Read-only Tag Details modal / JSON payload with QR & history.
     */
    public function show(Request $request, LaundryTag $tag): View|JsonResponse
    {
        $tag->load([
            'batch.printerProfile',
            'currentDeliveryPerson',
            'currentBooking.assignedStore',
            'activeBookingAssignment.verifiedByStoreUser',
            'allocations.deliveryPerson',
            'events' => fn ($q) => $q->with(['booking', 'deliveryPerson', 'store'])->limit(40),
        ]);

        $qrSvgDataUri = $this->qrService->renderSvgDataUri($tag->qr_payload);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'tag' => [
                    'id' => $tag->id,
                    'tag_code' => $tag->tag_code,
                    'status' => $tag->status,
                    'status_label' => $tag->status_label,
                    'print_status' => $tag->print_status,
                    'print_count' => $tag->print_count,
                ],
                'html' => view('admin::tags.partials.tag-details-modal-body', compact('tag', 'qrSvgDataUri'))->render(),
            ]);
        }

        return view('admin::tags.show', compact('tag', 'qrSvgDataUri'));
    }

    /**
     * 2. Generate & Print Tags screen.
     */
    public function generateForm(Request $request): View
    {
        $this->tagService->ensureDefaultPrinterProfiles($this->admin()->id);

        $profiles = TagPrinterProfile::orderByDesc('is_default')->orderBy('id')->get();
        $recentBatches = LaundryTagBatch::with(['printerProfile', 'generatedByAdmin'])
            ->orderByDesc('id')
            ->limit(15)
            ->get();
        $recentPrintJobs = LaundryTagPrintJob::with(['printerProfile', 'batch', 'requestedByAdmin'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $summary = $this->tagService->getInventorySummary();
        $defaultProfile = $profiles->firstWhere('is_default', true) ?? $profiles->first();
        $sampleQrDataUri = $this->qrService->renderSvgDataUri('FG-T-000001|SAMPLE', 1);

        return view('admin::tags.generate', compact(
            'profiles',
            'defaultProfile',
            'sampleQrDataUri',
            'recentBatches',
            'recentPrintJobs',
            'summary'
        ));
    }

    /**
     * AJAX or form submission to generate a new batch of unique tags.
     */
    public function generateStore(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . LaundryTagService::MAX_GENERATION_QUANTITY],
            'label' => ['nullable', 'string', 'max:120'],
            'printer_profile_id' => ['nullable', 'integer', 'exists:tag_printer_profiles,id'],
            'is_reusable' => ['nullable', 'boolean'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $batch = $this->tagService->generateBatch(
                (int) $validated['quantity'],
                $this->admin(),
                [
                    'label' => $validated['label'] ?? null,
                    'printer_profile_id' => $validated['printer_profile_id'] ?? null,
                    'is_reusable' => (bool) ($validated['is_reusable'] ?? false),
                ],
                $validated['idempotency_key'] ?? null
            );
        } catch (ValidationException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors())->withInput();
        }

        $msg = "Generated {$batch->quantity} unique laundry tags ({$batch->code_from} to {$batch->code_to}) in batch {$batch->batch_reference}.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'batch' => [
                    'id' => $batch->id,
                    'batch_reference' => $batch->batch_reference,
                    'quantity' => $batch->quantity,
                    'code_from' => $batch->code_from,
                    'code_to' => $batch->code_to,
                    'print_preview_url' => route('admin.tags.print-preview', ['batch_id' => $batch->id, 'profile_id' => $batch->printer_profile_id]),
                    'print_pdf_url' => route('admin.tags.print-pdf', ['batch_id' => $batch->id, 'profile_id' => $batch->printer_profile_id]),
                ],
                'recent_batches_html' => view('admin::tags.partials.recent-batches', [
                    'recentBatches' => LaundryTagBatch::with(['printerProfile', 'generatedByAdmin'])->orderByDesc('id')->limit(15)->get(),
                ])->render(),
            ]);
        }

        return redirect()->route('admin.tags.print-preview', [
            'batch_id' => $batch->id,
            'profile_id' => $batch->printer_profile_id,
        ])->with('success', $msg);
    }

    /**
     * Resolve tags/batch + profile for print preview and PDF generation.
     *
     * @return array{tags: \Illuminate\Support\Collection, batch: ?LaundryTagBatch, profile: TagPrinterProfile, copies: int, startOffset: int}
     */
    private function resolvePrintPayload(Request $request): array
    {
        $batchId = (int) $request->input('batch_id', 0);
        $rawIds = $request->input('tag_ids', []);
        if (is_string($rawIds)) {
            $rawIds = array_filter(explode(',', $rawIds));
        }

        $batch = $batchId > 0 ? LaundryTagBatch::with('printerProfile')->find($batchId) : null;

        if (! empty($rawIds)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $rawIds))));
            $tags = LaundryTag::whereIn('id', $ids)->orderBy('id')->get();
        } elseif ($batch) {
            $tags = $batch->tags()->get();
        } else {
            $limit = max(1, min(100, (int) $request->input('limit', 8)));
            $tags = LaundryTag::where('status', LaundryTag::STATUS_AVAILABLE)->orderByDesc('id')->limit($limit)->get()->reverse()->values();
        }

        $profileId = $request->filled('profile_id')
            ? (int) $request->input('profile_id')
            : ($batch?->printer_profile_id ?: null);
        $profile = $this->tagService->getActivePrinterProfile($profileId);

        $copies = max(1, min(5, (int) $request->input('copies', 1)));
        $startOffset = max(0, min(40, (int) $request->input('start_offset', 0)));

        return compact('tags', 'batch', 'profile', 'copies', 'startOffset');
    }

    /**
     * Print Preview page rendered in physical mm dimensions with Fresh Go hanging-tag design.
     */
    public function printPreview(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $payload = $this->resolvePrintPayload($request);
        if ($payload['tags']->isEmpty()) {
            return redirect()->route('admin.tags.generate')
                ->withErrors(['tag_ids' => 'No tags selected or available for printing.']);
        }

        $result = $this->tagService->recordPrintJob(
            $payload['tags']->pluck('id')->all(),
            $payload['batch']?->id,
            $payload['profile']->id,
            $payload['copies'],
            $payload['startOffset'],
            $this->admin(),
            LaundryTagPrintJob::STATUS_PREPARED
        );

        $slots = $this->buildPrintSlots($payload['tags'], $payload['copies'], $payload['startOffset'], $payload['profile']);
        $profiles = TagPrinterProfile::orderByDesc('is_default')->orderBy('id')->get();
        $brandLogoUrl = class_exists(Setting::class) ? Setting::getLogoUrl() : asset('images/logo.png');

        return view('admin::tags.print', [
            'tags' => $payload['tags'],
            'slots' => $slots,
            'pages' => array_chunk($slots, max(1, $payload['profile']->per_page_capacity)),
            'batch' => $payload['batch'],
            'profile' => $payload['profile'],
            'profiles' => $profiles,
            'copies' => $payload['copies'],
            'startOffset' => $payload['startOffset'],
            'printJob' => $result['print_job'],
            'warnings' => $result['warnings'],
            'brandLogoUrl' => $brandLogoUrl,
        ]);
    }

    /**
     * Download exact physical-scale PDF tag sheet using Dompdf.
     */
    public function downloadPdf(Request $request)
    {
        $payload = $this->resolvePrintPayload($request);
        if ($payload['tags']->isEmpty()) {
            return back()->withErrors(['tag_ids' => 'No tags selected or available for PDF export.']);
        }

        $result = $this->tagService->recordPrintJob(
            $payload['tags']->pluck('id')->all(),
            $payload['batch']?->id,
            $payload['profile']->id,
            $payload['copies'],
            $payload['startOffset'],
            $this->admin(),
            LaundryTagPrintJob::STATUS_REQUESTED
        );

        $slots = $this->buildPrintSlots($payload['tags'], $payload['copies'], $payload['startOffset'], $payload['profile']);
        $pages = array_chunk($slots, max(1, $payload['profile']->per_page_capacity));
        $profile = $payload['profile'];

        $logoDataUri = $this->resolveInlineLogoDataUri();

        // Dompdf paper size in points (1mm = 2.83464567pt)
        $widthPt = round($profile->paper_width_mm * 2.83464567, 2);
        $heightPt = round($profile->paper_height_mm * 2.83464567, 2);
        if ($profile->orientation === 'landscape') {
            [$widthPt, $heightPt] = [$heightPt, $widthPt];
        }

        $pdf = Pdf::loadView('admin::tags.print-pdf', [
            'pages' => $pages,
            'profile' => $profile,
            'printJob' => $result['print_job'],
            'logoDataUri' => $logoDataUri,
        ])->setPaper([0, 0, $widthPt, $heightPt]);

        $fileName = 'freshgo-laundry-tags-' . ($payload['batch']?->batch_reference ?? $result['print_job']->print_reference) . '.pdf';

        return $pdf->download($fileName);
    }

    public function confirmPrintJob(Request $request, LaundryTagPrintJob $printJob): JsonResponse
    {
        $confirmed = $this->tagService->confirmPrintJob($printJob, $this->admin());

        return response()->json([
            'success' => true,
            'message' => "Print job {$confirmed->print_reference} confirmed as printed.",
            'print_job' => [
                'id' => $confirmed->id,
                'status' => $confirmed->status,
                'confirmed_at' => $confirmed->confirmed_at?->format('d M Y, H:i'),
            ],
        ]);
    }

    /**
     * Build page slots honoring start_offset blanks and copies count.
     *
     * @return array<int, ?array<string, mixed>>
     */
    private function buildPrintSlots($tags, int $copies, int $startOffset, TagPrinterProfile $profile): array
    {
        $slots = [];
        for ($i = 0; $i < $startOffset; $i++) {
            $slots[] = null;
        }

        foreach ($tags as $tag) {
            $qrDataUri = $this->qrService->renderSvgDataUri($tag->qr_payload, 1);
            for ($c = 0; $c < $copies; $c++) {
                $slots[] = [
                    'tag' => $tag,
                    'tag_code' => $tag->tag_code,
                    'qr_data_uri' => $qrDataUri,
                ];
            }
        }

        return $slots;
    }

    private function resolveInlineLogoDataUri(): ?string
    {
        $logoSetting = class_exists(Setting::class) ? Setting::where('key', 'site_logo')->value('value') : null;
        if ($logoSetting) {
            $path = storage_path('app/public/' . ltrim($logoSetting, '/'));
            if (is_file($path)) {
                $mime = mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
            }
        }

        foreach ([public_path('images/logo.png'), public_path('favicon.ico')] as $candidate) {
            if (is_file($candidate)) {
                $mime = mime_content_type($candidate) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($candidate));
            }
        }

        return null;
    }

    /**
     * 3. Assign to Delivery Person page & AJAX stock/allocation list.
     */
    public function allocations(Request $request): View|JsonResponse
    {
        $summary = $this->tagService->getInventorySummary();
        $allocations = LaundryTagAllocation::with(['deliveryPerson', 'allocatedByAdmin', 'tags'])
            ->when($request->filled('delivery_person_id'), fn ($q) => $q->where('delivery_person_id', (int) $request->input('delivery_person_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->input('search'));
                $q->where('allocation_reference', 'like', "%{$s}%")
                    ->orWhereHas('deliveryPerson', fn ($dq) => $dq->where('full_name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            })
            ->orderByDesc('allocated_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $deliveryPersons = DeliveryPerson::where('work_status', 'active')
            ->withCount([
                'allocatedLaundryTags as allocated_unused_count' => fn ($q) => $q->where('status', LaundryTag::STATUS_ALLOCATED),
                'allocatedLaundryTags as active_assigned_count' => fn ($q) => $q->whereIn('status', LaundryTag::ACTIVE_BOOKING_STATUSES),
            ])
            ->orderBy('full_name')
            ->get();

        $batchesWithAvailable = LaundryTagBatch::withCount([
            'tags as available_tags_count' => fn ($q) => $q->where('status', LaundryTag::STATUS_AVAILABLE),
        ])
            ->orderByDesc('id')
            ->limit(60)
            ->get()
            ->filter(fn ($b) => $b->available_tags_count > 0)
            ->values();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'table_html' => view('admin::tags.partials.allocations-table', compact('allocations'))->render(),
            ]);
        }

        return view('admin::tags.allocations', compact(
            'allocations',
            'deliveryPersons',
            'batchesWithAvailable',
            'summary'
        ));
    }

    /**
     * Store Delivery Person allocation via modal AJAX form.
     */
    public function storeAllocation(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'delivery_person_id' => ['required', 'integer', 'exists:delivery_persons,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'batch_id' => ['nullable', 'integer', 'exists:laundry_tag_batches,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $dp = DeliveryPerson::findOrFail((int) $validated['delivery_person_id']);

        try {
            $allocation = $this->tagService->allocateToDeliveryPerson(
                $dp,
                (int) $validated['quantity'],
                $this->admin(),
                ! empty($validated['batch_id']) ? (int) $validated['batch_id'] : null,
                $validated['notes'] ?? null
            );
        } catch (ValidationException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors())->withInput();
        }

        $msg = "Allocated {$allocation->quantity} laundry tags to {$dp->full_name} ({$allocation->allocation_reference}).";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'summary' => $this->tagService->getInventorySummary(),
                'allocation' => [
                    'id' => $allocation->id,
                    'reference' => $allocation->allocation_reference,
                    'quantity' => $allocation->quantity,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Exception/retire action on a tag (Lost, Damaged, Voided, Retired).
     */
    public function reportException(Request $request, LaundryTag $tag): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:lost,damaged,voided,retired'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'allow_release' => ['nullable', 'boolean'],
        ]);

        $admin = $this->admin();

        try {
            $updated = $this->tagService->transitionExceptionOrRetire(
                $tag,
                $validated['status'],
                $validated['reason'],
                'admin',
                $admin->id,
                $admin->name,
                (bool) ($validated['allow_release'] ?? false)
            );
        } catch (ValidationException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $msg = "Updated tag {$updated->tag_code} to {$updated->status_label}.";

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'status' => $updated->status, 'status_label' => $updated->status_label])
            : back()->with('success', $msg);
    }

    /**
     * Recover undamaged/unlost tag back to Available Admin stock.
     */
    public function returnToStock(Request $request, LaundryTag $tag): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $updated = $this->tagService->returnTagToAvailableStock($tag, $this->admin(), $validated['reason']);
        } catch (ValidationException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $msg = "Returned tag {$updated->tag_code} to Available Admin inventory.";

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'status' => $updated->status, 'status_label' => $updated->status_label])
            : back()->with('success', $msg);
    }

    /**
     * 4. Tag History (Audit Log) screen & AJAX endpoint.
     */
    public function history(Request $request): View|JsonResponse
    {
        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedYear = (int) $request->input('year', now()->year);
        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = now()->month;
        }
        if ($selectedYear < 2020 || $selectedYear > 2100) {
            $selectedYear = now()->year;
        }

        $periodDate = \Illuminate\Support\Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $prevDate = $periodDate->copy()->subMonth();
        $nextDate = $periodDate->copy()->addMonth();

        $filters = $request->only([
            'tag_code',
            'booking_reference',
            'delivery_person_id',
            'store_id',
            'event_type',
            'date_from',
            'date_to',
            'all_months',
        ]);

        // Default to selected month unless explicit date_from/date_to, tag_code, booking_reference, or all_months is used
        $hasSpecificSearch = ! empty($filters['date_from']) || ! empty($filters['date_to']) || ! empty($filters['tag_code']) || ! empty($filters['booking_reference']) || ! empty($filters['all_months']);
        if (! $hasSpecificSearch) {
            $filters['date_from'] = $periodDate->copy()->startOfMonth()->toDateString();
            $filters['date_to'] = $periodDate->copy()->endOfMonth()->toDateString();
        }

        $events = $this->tagService->buildFilteredEventsQuery($filters)
            ->paginate(25)
            ->withQueryString();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'month_label' => $periodDate->format('F Y'),
                'prev_month' => $prevDate->month,
                'prev_year' => $prevDate->year,
                'next_month' => $nextDate->month,
                'next_year' => $nextDate->year,
                'table_html' => view('admin::tags.partials.history-table', compact('events'))->render(),
            ]);
        }

        $deliveryPersons = DeliveryPerson::orderBy('full_name')->get();
        $stores = Store::orderBy('name')->get();
        $eventTypes = [
            LaundryTagEvent::EVENT_GENERATED => 'Generated',
            LaundryTagEvent::EVENT_PRINT_PREPARED => 'Print Prepared',
            LaundryTagEvent::EVENT_PRINT_REQUESTED => 'Print Requested / PDF',
            LaundryTagEvent::EVENT_PRINT_CONFIRMED => 'Print Confirmed',
            LaundryTagEvent::EVENT_ALLOCATED_TO_DP => 'Allocated to Delivery Person',
            LaundryTagEvent::EVENT_ASSIGNED_TO_BOOKING => 'Assigned to Booking',
            LaundryTagEvent::EVENT_UNASSIGNED_FROM_BOOKING => 'Unassigned from Booking',
            LaundryTagEvent::EVENT_HANDED_OVER_REQUESTED => 'Handover Requested',
            LaundryTagEvent::EVENT_STORE_VERIFIED => 'Verified at Store',
            LaundryTagEvent::EVENT_MARKED_AT_STORE => 'Marked At Store',
            LaundryTagEvent::EVENT_HANDOVER_VERIFICATION_ISSUE => 'Verification Mismatch',
            LaundryTagEvent::EVENT_REPORTED_LOST => 'Reported Lost',
            LaundryTagEvent::EVENT_REPORTED_DAMAGED => 'Reported Damaged',
            LaundryTagEvent::EVENT_VOIDED => 'Voided',
            LaundryTagEvent::EVENT_RETIRED => 'Retired',
            LaundryTagEvent::EVENT_RETURNED_TO_STOCK => 'Returned to Available Stock',
        ];

        return view('admin::tags.history', [
            'events' => $events,
            'deliveryPersons' => $deliveryPersons,
            'stores' => $stores,
            'eventTypes' => $eventTypes,
            'filters' => $filters,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthLabel' => $periodDate->format('F Y'),
            'prevMonth' => $prevDate->month,
            'prevYear' => $prevDate->year,
            'nextMonth' => $nextDate->month,
            'nextYear' => $nextDate->year,
        ]);
    }

    /**
     * 5. Printer Settings screen.
     */
    public function printerSettings(Request $request): View
    {
        $this->tagService->ensureDefaultPrinterProfiles($this->admin()->id);
        $profiles = TagPrinterProfile::orderByDesc('is_default')->orderBy('id')->get();
        $editingProfile = $request->filled('edit')
            ? TagPrinterProfile::find((int) $request->input('edit'))
            : $profiles->first();

        return view('admin::tags.printer-settings', compact('profiles', 'editingProfile'));
    }

    public function savePrinterSettings(Request $request, ?TagPrinterProfile $profile = null): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'preset' => ['required', 'in:a4,letter,custom,roll'],
            'orientation' => ['required', 'in:portrait,landscape'],
            'paper_width_mm' => ['required', 'numeric', 'min:25', 'max:600'],
            'paper_height_mm' => ['required', 'numeric', 'min:40', 'max:1000'],
            'tag_width_mm' => ['required', 'numeric', 'min:20', 'max:250'],
            'tag_height_mm' => ['required', 'numeric', 'min:40', 'max:350'],
            'margin_top_mm' => ['required', 'numeric', 'min:0', 'max:60'],
            'margin_bottom_mm' => ['required', 'numeric', 'min:0', 'max:60'],
            'margin_left_mm' => ['required', 'numeric', 'min:0', 'max:60'],
            'margin_right_mm' => ['required', 'numeric', 'min:0', 'max:60'],
            'gap_horizontal_mm' => ['required', 'numeric', 'min:0', 'max:50'],
            'gap_vertical_mm' => ['required', 'numeric', 'min:0', 'max:50'],
            'tags_per_row' => ['required', 'integer', 'min:1', 'max:12'],
            'tags_per_column' => ['required', 'integer', 'min:1', 'max:12'],
            'qr_size_mm' => ['required', 'numeric', 'min:10', 'max:80'],
            'logo_size_mm' => ['required', 'numeric', 'min:6', 'max:60'],
            'font_size_pt' => ['required', 'numeric', 'min:5', 'max:20'],
            'code_font_size_pt' => ['required', 'numeric', 'min:6', 'max:24'],
            'show_brand_name' => ['nullable', 'boolean'],
            'show_booking_field' => ['nullable', 'boolean'],
            'show_package_field' => ['nullable', 'boolean'],
            'show_cut_guides' => ['nullable', 'boolean'],
            'show_hole_guide' => ['nullable', 'boolean'],
            'background_printing' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $saved = $this->tagService->savePrinterProfile($validated, $this->admin(), $profile);
        $msg = "Saved printer profile \"{$saved->name}\".";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'profile_id' => $saved->id,
            ]);
        }

        return redirect()->route('admin.tags.printer-settings.index', ['edit' => $saved->id])->with('success', $msg);
    }
}
