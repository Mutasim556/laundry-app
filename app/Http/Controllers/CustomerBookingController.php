<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerBookingController extends Controller
{
    protected function canAccessBooking(Request $request, Booking $booking): bool
    {
        if ($booking->user_id !== null) {
            return $request->user() !== null && (int) $booking->user_id === (int) $request->user()->id;
        }

        if (session('last_confirmed_booking_reference') === $booking->reference) {
            return true;
        }

        if (session('booking.reference') === $booking->reference && ! empty(session('booking.confirmed_at'))) {
            return true;
        }

        $verified = session('verified_order_references', []);

        return is_array($verified) && in_array($booking->reference, $verified, true);
    }

    public function success(Request $request, string $reference): View
    {
        $booking = Booking::where('reference', $reference)->firstOrFail();
        abort_unless($this->canAccessBooking($request, $booking), 403, 'You are not authorized to view this booking.');

        return view('booking.success', [
            'booking' => $booking,
        ]);
    }

    public function index(Request $request, CurrencyService $currencyService): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        Booking::associateVerifiedUserBookings($user);

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'preset' => ['nullable', 'string', 'in:all,today,last_7_days,last_30_days,this_month'],
            'status' => ['nullable', 'string', 'in:all,pending,in_progress,completed,cancelled'],
            'q' => ['nullable', 'string', 'max:60'],
        ]);

        $tz = (string) config('app.timezone', 'UTC');
        $preset = (string) ($validated['preset'] ?? 'all');
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;

        if ($preset !== 'all' && ! $fromDate && ! $toDate) {
            $now = now($tz);
            match ($preset) {
                'today' => [$fromDate, $toDate] = [$now->toDateString(), $now->toDateString()],
                'last_7_days' => [$fromDate, $toDate] = [$now->copy()->subDays(6)->toDateString(), $now->toDateString()],
                'last_30_days' => [$fromDate, $toDate] = [$now->copy()->subDays(29)->toDateString(), $now->toDateString()],
                'this_month' => [$fromDate, $toDate] = [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()],
                default => null,
            };
        }

        $statusFilter = (string) ($validated['status'] ?? 'all');
        $search = trim((string) ($validated['q'] ?? ''));

        $baseQuery = Booking::query()
            ->where('user_id', $user->id)
            ->whereNotNull('confirmed_at');

        // Global all-time customer statistics using single shared grouping logic
        $allCustomerBookings = (clone $baseQuery)
            ->select(['id', 'booking_status', 'completed_operational_stages'])
            ->get();

        $stats = [
            'total' => $allCustomerBookings->count(),
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        foreach ($allCustomerBookings as $row) {
            $group = $row->getCustomerStatusGroup();
            $stats[$group] = ($stats[$group] ?? 0) + 1;
        }

        $filteredQuery = (clone $baseQuery)
            ->with([
                'assignedStore:id,name',
                'currentStoreAssignment:id,booking_id,store_name_snapshot',
                'currentInvoice:id,booking_id,invoice_number',
                'operationalItems',
            ]);

        if ($fromDate) {
            $filteredQuery->where('created_at', '>=', \Carbon\Carbon::parse($fromDate, $tz)->startOfDay()->utc());
        }
        if ($toDate) {
            $filteredQuery->where('created_at', '<=', \Carbon\Carbon::parse($toDate, $tz)->endOfDay()->utc());
        }

        if ($statusFilter !== 'all') {
            match ($statusFilter) {
                Booking::CUSTOMER_STATUS_CANCELLED => $filteredQuery->where('booking_status', Booking::STATUS_CANCELLED),
                Booking::CUSTOMER_STATUS_COMPLETED => $filteredQuery->where(function ($q) {
                    $q->whereIn('booking_status', [Booking::STATUS_COMPLETED, Booking::STATUS_DELIVERED])
                        ->orWhere('completed_operational_stages', 'like', '%"' . Booking::STATUS_COMPLETED . '"%');
                }),
                Booking::CUSTOMER_STATUS_PENDING => $filteredQuery
                    ->whereIn('booking_status', Booking::CUSTOMER_PENDING_BOOKING_STATUSES)
                    ->where(function ($q) {
                        $q->whereNull('completed_operational_stages')
                            ->orWhere('completed_operational_stages', 'not like', '%"' . Booking::STATUS_PROCESSING . '"%');
                    }),
                Booking::CUSTOMER_STATUS_IN_PROGRESS => $filteredQuery
                    ->whereNotIn('booking_status', [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED, Booking::STATUS_DELIVERED])
                    ->where(function ($q) {
                        $q->whereNull('completed_operational_stages')
                            ->orWhere('completed_operational_stages', 'not like', '%"' . Booking::STATUS_COMPLETED . '"%');
                    })
                    ->where(function ($q) {
                        $q->whereNotIn('booking_status', Booking::CUSTOMER_PENDING_BOOKING_STATUSES)
                            ->orWhere('completed_operational_stages', 'like', '%"' . Booking::STATUS_PROCESSING . '"%');
                    }),
                default => null,
            };
        }

        if ($search !== '') {
            $filteredQuery->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('currentInvoice', fn ($iq) => $iq->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        $bookings = $filteredQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $hasActiveFilters = $fromDate !== null
            || $toDate !== null
            || $statusFilter !== 'all'
            || $search !== ''
            || $preset !== 'all';

        return view('orders.index', [
            'bookings' => $bookings,
            'stats' => $stats,
            'currencyService' => $currencyService,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'preset' => $preset,
                'status' => $statusFilter,
                'q' => $search,
            ],
            'hasActiveFilters' => $hasActiveFilters,
        ]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:160'],
        ]);

        $ref = strtoupper(trim($validated['reference']));
        $email = strtolower(trim($validated['email']));

        $booking = Booking::where('reference', $ref)
            ->whereRaw('LOWER(customer_email) = ?', [$email])
            ->whereNotNull('confirmed_at')
            ->first();

        if (! $booking) {
            return back()
                ->withErrors(['reference' => 'No confirmed booking matched that reference and email address.'])
                ->withInput();
        }

        $verified = session('verified_order_references', []);
        if (! is_array($verified)) {
            $verified = [];
        }
        if (! in_array($booking->reference, $verified, true)) {
            $verified[] = $booking->reference;
            $request->session()->put('verified_order_references', $verified);
        }

        return redirect()->route('orders.show', $booking->reference);
    }

    public function show(Request $request, string $reference, CurrencyService $currencyService): View
    {
        $booking = Booking::where('reference', $reference)->firstOrFail();
        abort_unless($this->canAccessBooking($request, $booking), 403, 'You are not authorized to view this booking.');

        $booking->syncOperationalItemsFromSnapshotIfEmpty($currencyService);
        $booking->load([
            'operationalItems.category',
            'operationalItems.item',
            'currentStoreAssignment',
            'currentCollectionAssignment.deliveryPerson',
            'currentFinalDeliveryAssignment.deliveryPerson',
        ]);

        $pricingSummary = $booking->getOperationalPricingSummary($currencyService);
        $categoryGroups = $booking->getActiveOperationalCategoryGroups();
        $stageProgress = $booking->getOperationalStageProgress();

        return view('orders.show', [
            'booking' => $booking,
            'pricingSummary' => $pricingSummary,
            'categoryGroups' => $categoryGroups,
            'currencyService' => $currencyService,
            'stageProgress' => $stageProgress,
        ]);
    }

    public function rejectMutation(Request $request, string $reference): JsonResponse|RedirectResponse
    {
        $message = 'Confirmed bookings are permanently locked and cannot be modified or deleted.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        abort(403, $message);
    }
}
