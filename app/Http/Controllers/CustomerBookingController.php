<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\CollectionPaymentService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

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

    public function show(Request $request, string $reference, CurrencyService $currencyService, CollectionPaymentService $paymentService): View
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
            'customerPayments' => fn ($q) => $q->orderByDesc('id'),
            'claims.items',
            'claims.evidence',
            'claims.refundTransactions',
        ]);

        $pricingSummary = $booking->getOperationalPricingSummary($currencyService);
        $paymentState = $paymentService->getPaymentState($booking);
        $categoryGroups = $booking->getActiveOperationalCategoryGroups();
        $stageProgress = $booking->getOperationalStageProgress();
        $claimEligibility = app(\App\Services\PolicyResolutionService::class)->getCustomerEligibility($booking);

        return view('orders.show', [
            'booking' => $booking,
            'pricingSummary' => $pricingSummary,
            'paymentState' => $paymentState,
            'categoryGroups' => $categoryGroups,
            'currencyService' => $currencyService,
            'stageProgress' => $stageProgress,
            'claimEligibility' => $claimEligibility,
        ]);
    }

    public function createStripePaymentIntent(Request $request, string $reference, CollectionPaymentService $paymentService): JsonResponse
    {
        $booking = Booking::where('reference', $reference)->firstOrFail();
        abort_unless($request->user() !== null && $this->canAccessBooking($request, $booking), 403, 'You are not authorized to pay for this booking.');

        try {
            $intent = $paymentService->initiateCustomerStripePaymentIntent($booking);

            return response()->json([
                'success' => true,
                'publishable_key' => $intent['publishable_key'],
                'client_secret' => $intent['client_secret'],
                'payment_intent_id' => $intent['payment_intent_id'],
                'amount_minor' => $intent['amount_minor'],
                'currency' => $intent['currency'],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Customer Stripe PaymentIntent creation failed', [
                'booking_id' => $booking->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initialize Stripe payment right now. Please try again later.',
            ], 500);
        }
    }

    public function payWithStripe(Request $request, string $reference, CollectionPaymentService $paymentService): RedirectResponse
    {
        $booking = Booking::where('reference', $reference)->firstOrFail();
        abort_unless($request->user() !== null && $this->canAccessBooking($request, $booking), 403, 'You are not authorized to pay for this booking.');

        try {
            $session = $paymentService->initiateCustomerStripeCheckout($booking);
            $checkoutUrl = (string) ($session['checkout_url'] ?? '');
            if ($checkoutUrl === '') {
                return back()->with('error', 'Unable to start Stripe checkout right now. Please try again.');
            }

            return redirect()->away($checkoutUrl);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Customer Stripe checkout initiation failed', [
                'booking_id' => $booking->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to start Stripe payment right now. Please try again later.');
        }
    }

    public function payWithPayPal(Request $request, string $reference, CollectionPaymentService $paymentService): RedirectResponse
    {
        $booking = Booking::where('reference', $reference)->firstOrFail();
        abort_unless($request->user() !== null && $this->canAccessBooking($request, $booking), 403, 'You are not authorized to pay for this booking.');

        try {
            $order = $paymentService->initiateCustomerPayPalOrder($booking);
            $approveUrl = (string) ($order['approve_url'] ?? '');
            if ($approveUrl === '') {
                return back()->with('error', 'Unable to start PayPal checkout right now. Please try again.');
            }

            return redirect()->away($approveUrl);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Customer PayPal order initiation failed', [
                'booking_id' => $booking->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to start PayPal payment right now. Please try again later.');
        }
    }

    public function paymentStatus(Request $request, string $reference, CurrencyService $currencyService, CollectionPaymentService $paymentService, \App\Services\StripePaymentService $stripeService): JsonResponse
    {
        $booking = Booking::where('reference', $reference)->firstOrFail();
        abort_unless($this->canAccessBooking($request, $booking), 403, 'You are not authorized to view this booking.');

        $state = $paymentService->getPaymentState($booking);

        // Server-side reconciliation fallback if Stripe PaymentIntent succeeded before webhook delivery
        if (! $state['is_payment_satisfied']
            && strtolower((string) $booking->payment_gateway) === 'stripe'
            && ! empty($booking->stripe_payment_intent_id)
            && $stripeService->isActive()) {
            try {
                $pi = $stripeService->retrievePaymentIntent((string) $booking->stripe_payment_intent_id);
                if (strtolower((string) ($pi->status ?? '')) === 'succeeded') {
                    $amountMinor = (int) ($pi->amount_received ?? $pi->amount ?? 0);
                    $currency = strtoupper((string) ($pi->currency ?? $booking->currency ?? 'GBP'));
                    if ($amountMinor > 0) {
                        $paymentService->recordVerifiedGatewayPayment(
                            $booking,
                            \App\Models\BookingCustomerPayment::METHOD_STRIPE,
                            (string) $pi->id,
                            $amountMinor,
                            $currency,
                            'Verified via Stripe PaymentIntent API'
                        );
                        $booking->refresh();
                        $state = $paymentService->getPaymentState($booking);
                    }
                }
            } catch (\Throwable) {
                // Non-blocking; webhook or next poll will reconcile
            }
        }

        $completedStages = $booking->getCompletedOperationalStatuses();

        return response()->json([
            'otp_verified' => $booking->collection_otp_verified_at !== null,
            'is_pricing_complete' => $state['is_pricing_complete'],
            'payment_status' => $booking->payment_status,
            'payment_status_label' => $booking->getFinancialPaymentStatusLabel(),
            'confirmed_paid_minor' => $state['confirmed_paid_minor'],
            'outstanding_minor' => $state['outstanding_minor'],
            'formatted_paid' => $currencyService->formatMinor($state['confirmed_paid_minor'], $state['currency']),
            'formatted_outstanding' => $currencyService->formatMinor($state['outstanding_minor'], $state['currency']),
            'is_payment_satisfied' => $state['is_payment_satisfied'],
            'booking_status' => $booking->booking_status,
            'booking_status_label' => $booking->getStatusLabel(),
            'is_finalized' => in_array(Booking::STATUS_FINALIZED, $completedStages, true),
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
