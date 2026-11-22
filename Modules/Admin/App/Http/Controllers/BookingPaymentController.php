<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingPaymentController extends Controller
{
    public function __construct(
        protected StripePaymentService $stripe
    ) {}

    public function index(Request $request): View
    {
        $query = Booking::query()->latest('id');

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('payment_gateway')) {
            $query->where('payment_gateway', $request->input('payment_gateway'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('reference', 'like', '%' . $term . '%')
                    ->orWhere('customer_name', 'like', '%' . $term . '%')
                    ->orWhere('customer_email', 'like', '%' . $term . '%');
            });
        }

        $bookings = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => Booking::whereIn('payment_status', ['pending_item_count', 'awaiting_final_amount'])->count(),
            'authorized' => Booking::where('payment_status', 'authorized_for_later_charge')->count(),
            'paid' => Booking::where('payment_status', 'paid')->count(),
            'failed' => Booking::whereIn('payment_status', ['failed', 'requires_action'])->count(),
        ];

        return view('admin::payments.index', compact('bookings', 'stats'));
    }

    /**
     * Save verified final order amount (after weighing/inspection) and optionally charge authorized Stripe card.
     */
    public function finalizeAndCharge(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'final_amount' => ['required', 'numeric', 'min:0.50', 'max:100000'],
            'initiate_charge' => ['nullable', 'boolean'],
        ]);

        $amountMinor = (int) round(((float) $validated['final_amount']) * 100);
        $booking->final_amount_minor = $amountMinor;

        if (! $request->boolean('initiate_charge')) {
            if ($booking->payment_status === 'pending_item_count') {
                $booking->payment_status = 'awaiting_final_amount';
            }
            $booking->save();

            return response()->json([
                'success' => true,
                'message' => "Final amount saved for {$booking->reference}.",
                'booking' => $booking->fresh(),
            ]);
        }

        if ($booking->payment_gateway !== 'stripe') {
            $booking->save();

            return response()->json([
                'success' => false,
                'message' => 'Off-session card charging is only available for Stripe bookings.',
            ], 422);
        }

        if (! $booking->consent_accepted || ! $booking->stripe_payment_method_id || ! $booking->stripe_customer_id) {
            $booking->save();

            return response()->json([
                'success' => false,
                'message' => 'Customer authorization or Stripe PaymentMethod is missing for this booking.',
            ], 422);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This booking is already marked as paid.',
            ], 422);
        }

        $idempotencyKey = "booking-{$booking->id}-final-{$amountMinor}";
        $result = $this->stripe->chargeSavedPaymentMethod($booking, $amountMinor, $idempotencyKey);

        $booking->stripe_payment_intent_id = $result['payment_intent_id'] ?? $booking->stripe_payment_intent_id;

        if ($result['status'] === 'succeeded') {
            $booking->payment_status = 'paid';
            $booking->paid_at = now();
            $booking->last_payment_error = null;
        } elseif ($result['status'] === 'requires_action') {
            $booking->payment_status = 'requires_action';
            $booking->last_payment_error = $result['error'];
        } elseif ($result['status'] === 'processing') {
            $booking->payment_status = 'payment_processing';
            $booking->last_payment_error = null;
        } else {
            $booking->payment_status = 'failed';
            $booking->last_payment_error = $result['error'] ?? 'Payment declined.';
        }

        $booking->save();

        return response()->json([
            'success' => $result['status'] === 'succeeded' || $result['status'] === 'processing',
            'status' => $booking->payment_status,
            'message' => $result['status'] === 'succeeded'
                ? "Payment succeeded for {$booking->reference}."
                : ($result['error'] ?? "Payment status: {$booking->payment_status}"),
            'booking' => $booking->fresh(),
        ], in_array($result['status'], ['succeeded', 'processing'], true) ? 200 : 422);
    }

    /**
     * Retry an eligible failed Stripe charge using the stored final_amount_minor.
     */
    public function retryCharge(Booking $booking): JsonResponse
    {
        if ($booking->payment_gateway !== 'stripe' || $booking->final_amount_minor === null) {
            return response()->json([
                'success' => false,
                'message' => 'Booking is not eligible for Stripe retry.',
            ], 422);
        }

        if (! in_array($booking->payment_status, ['failed', 'requires_action', 'authorized_for_later_charge'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot retry booking in status '{$booking->payment_status}'.",
            ], 422);
        }

        $idempotencyKey = "booking-{$booking->id}-retry-" . now()->format('YmdHis');
        $result = $this->stripe->chargeSavedPaymentMethod($booking, (int) $booking->final_amount_minor, $idempotencyKey);

        $booking->stripe_payment_intent_id = $result['payment_intent_id'] ?? $booking->stripe_payment_intent_id;
        if ($result['status'] === 'succeeded') {
            $booking->payment_status = 'paid';
            $booking->paid_at = now();
            $booking->last_payment_error = null;
        } elseif ($result['status'] === 'requires_action') {
            $booking->payment_status = 'requires_action';
            $booking->last_payment_error = $result['error'];
        } else {
            $booking->payment_status = 'failed';
            $booking->last_payment_error = $result['error'] ?? 'Retry failed.';
        }

        $booking->save();

        return response()->json([
            'success' => $result['status'] === 'succeeded',
            'status' => $booking->payment_status,
            'message' => $result['status'] === 'succeeded'
                ? "Retry payment succeeded for {$booking->reference}."
                : ($result['error'] ?? 'Retry failed.'),
        ], $result['status'] === 'succeeded' ? 200 : 422);
    }

    /**
     * Issue a refund for a paid Stripe booking.
     */
    public function refund(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->payment_gateway !== 'stripe' || $booking->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Only paid Stripe bookings can be refunded.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.50'],
        ]);

        $amountMinor = isset($validated['amount'])
            ? (int) round(((float) $validated['amount']) * 100)
            : (int) $booking->final_amount_minor;

        $result = $this->stripe->refundBooking($booking, $amountMinor);

        if (! $result['success']) {
            Log::warning('Admin Stripe refund failed', ['booking_id' => $booking->id, 'error' => $result['error']]);

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Refund failed.',
            ], 422);
        }

        $booking->update([
            'stripe_refund_id' => $result['refund_id'],
            'refund_status' => $result['status'] ?? 'succeeded',
            'refunded_amount_minor' => $amountMinor,
            'payment_status' => 'refunded',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Refund initiated for {$booking->reference} ({$result['refund_id']}).",
        ]);
    }
}
