<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\CustomerPaymentMethod;
use App\Models\StripeWebhookEvent;
use App\Services\CollectionPaymentService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class StripePaymentController extends Controller
{
    public function __construct(
        protected StripePaymentService $stripe,
        protected CollectionPaymentService $collectionPaymentService
    ) {}

    /**
     * Create an off-session SetupIntent for Booking Step 5 (never charges an unknown amount).
     */
    public function createSetupIntent(Request $request): JsonResponse
    {
        if (! $this->stripe->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe test/live keys are not configured in .env.',
            ], 503);
        }

        $booking = $request->session()->get('booking', []);
        if (empty($booking['services']) || empty($booking['address']['selected_address']) || empty($booking['details']['customer_email'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete earlier booking steps before setting up card payment.',
            ], 422);
        }

        $email = (string) $booking['details']['customer_email'];
        $name = (string) ($booking['details']['customer_name'] ?? '');
        $userId = $request->user()?->id;

        try {
            $customerId = $this->stripe->getOrCreateCustomer($email, $name, $userId);
            $setup = $this->stripe->createSetupIntent($customerId, [
                'customer_email' => $email,
                'booking_reference' => (string) ($booking['reference'] ?? ''),
            ]);

            $request->session()->put('booking.stripe_customer_id', $customerId);
            $request->session()->put('booking.stripe_setup_intent_id', $setup['id']);

            return response()->json([
                'success' => true,
                'client_secret' => $setup['client_secret'],
                'setup_intent_id' => $setup['id'],
                'customer_id' => $customerId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe SetupIntent creation failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initialize secure card setup. Please try again or choose another payment method.',
            ], 500);
        }
    }

    /**
     * List saved masked payment methods for the authenticated customer.
     */
    public function savedCards(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'cards' => []], 401);
        }

        $cards = CustomerPaymentMethod::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CustomerPaymentMethod $pm) => [
                'id' => $pm->id,
                'stripe_payment_method_id' => $pm->stripe_payment_method_id,
                'brand' => strtoupper($pm->card_brand),
                'last4' => $pm->card_last4,
                'exp_month' => str_pad((string) $pm->card_exp_month, 2, '0', STR_PAD_LEFT),
                'exp_year' => $pm->card_exp_year,
                'label' => $pm->display_label,
                'is_default' => (bool) $pm->is_default,
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'cards' => $cards,
        ]);
    }

    /**
     * Remove a saved card owned by the authenticated customer.
     */
    public function destroySavedCard(Request $request, CustomerPaymentMethod $paymentMethod): JsonResponse
    {
        $user = $request->user();
        if (! $user || (int) $paymentMethod->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($this->stripe->isConfigured()) {
            try {
                $this->stripe->detachPaymentMethod($paymentMethod->stripe_payment_method_id);
            } catch (\Throwable $e) {
                Log::warning('Stripe detach failed during local removal', ['pm_id' => $paymentMethod->id]);
            }
        }

        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Saved payment method removed.',
        ]);
    }

    /**
     * Customer return URL from Stripe Checkout Session (signed URL).
     * Verifies payment directly with Stripe API before recording.
     */
    public function checkoutReturn(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if (! $request->hasValidSignatureWhileIgnoring(['session_id'])) {
            abort(403, 'Invalid or expired Stripe return signature.');
        }

        $sessionId = trim((string) $request->query('session_id', ''));
        if ($sessionId === '') {
            return redirect()->route('booking.success', $booking->reference)
                ->with('error', 'Missing Stripe checkout session reference.');
        }

        try {
            $session = $this->stripe->retrieveCheckoutSession($sessionId);
            $metaBookingId = (string) ($session->metadata->booking_id ?? $session->client_reference_id ?? '');
            if ($metaBookingId !== '' && $metaBookingId !== (string) $booking->id) {
                return redirect()->route('booking.success', $booking->reference)
                    ->with('error', 'Stripe session does not match this booking.');
            }

            $paymentStatus = strtolower((string) ($session->payment_status ?? ''));
            if ($paymentStatus === 'paid') {
                $amountMinor = (int) ($session->amount_total ?? 0);
                $currency = strtoupper((string) ($session->currency ?? $booking->currency ?? 'GBP'));
                $piId = is_object($session->payment_intent ?? null)
                    ? (string) ($session->payment_intent->id ?? '')
                    : (string) ($session->payment_intent ?? '');

                if ($piId !== '' && $booking->stripe_payment_intent_id !== $piId) {
                    $booking->forceFill(['stripe_payment_intent_id' => $piId])->save();
                }

                $this->collectionPaymentService->recordVerifiedGatewayPayment(
                    $booking,
                    BookingCustomerPayment::METHOD_STRIPE,
                    $sessionId,
                    $amountMinor,
                    $currency,
                    'Verified via Stripe Checkout return'
                );

                if ($request->expectsJson()) {
                    return response()->json(['status' => 'paid', 'session_id' => $sessionId]);
                }

                return redirect()->route('orders.show', $booking->reference)
                    ->with('success', 'Stripe payment confirmed successfully.');
            }

            if ($request->expectsJson()) {
                return response()->json(['status' => 'pending', 'session_id' => $sessionId]);
            }

            return redirect()->route('orders.show', $booking->reference)
                ->with('info', 'Stripe payment is still processing. Your order will update once confirmed.');
        } catch (\Throwable $e) {
            Log::error('Stripe checkout return verification failed', [
                'booking_id' => $booking->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('orders.show', $booking->reference)
                ->with('error', 'Unable to verify Stripe payment right now. Please contact support if charged.');
        }
    }

    /**
     * Customer cancel URL from Stripe Checkout Session (signed URL).
     */
    public function checkoutCancel(Request $request, Booking $booking): RedirectResponse
    {
        return redirect()->route('orders.show', $booking->reference)
            ->with('error', 'Stripe payment was cancelled. No charge was made.');
    }

    /**
     * Verified, idempotent Stripe Webhook handler.
     * POST /stripe/webhook (CSRF exempt)
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');

        try {
            $event = $this->stripe->constructWebhookEvent($payload, $sigHeader);
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid webhook signature.'], 400);
        }

        // Idempotency guard: ignore duplicate event deliveries
        if (StripeWebhookEvent::where('stripe_event_id', $event->id)->exists()) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        $object = $event->data->object ?? null;
        $eventType = (string) $event->type;

        try {
            switch ($eventType) {
                case 'setup_intent.succeeded':
                    if ($object && ! empty($object->id)) {
                        Booking::where('stripe_setup_intent_id', $object->id)->update([
                            'stripe_payment_method_id' => $object->payment_method ?? null,
                            'payment_status' => 'authorized_for_later_charge',
                            'last_payment_error' => null,
                        ]);
                    }
                    break;

                case 'setup_intent.setup_failed':
                    if ($object && ! empty($object->id)) {
                        Booking::where('stripe_setup_intent_id', $object->id)->update([
                            'payment_status' => 'failed',
                            'last_payment_error' => $object->last_setup_error->message ?? 'Card setup failed.',
                        ]);
                    }
                    break;

                case 'checkout.session.completed':
                case 'checkout.session.async_payment_succeeded':
                    if ($object && ! empty($object->id)) {
                        $sessionStatus = strtolower((string) ($object->payment_status ?? ''));
                        if ($sessionStatus !== 'paid') {
                            break;
                        }
                        $bookingId = (int) ($object->metadata->booking_id ?? $object->client_reference_id ?? 0);
                        $booking = $bookingId > 0 ? Booking::find($bookingId) : null;
                        if ($booking) {
                            $amountMinor = (int) ($object->amount_total ?? 0);
                            $currency = strtoupper((string) ($object->currency ?? $booking->currency ?? 'GBP'));
                            if ($amountMinor > 0) {
                                $this->collectionPaymentService->recordVerifiedGatewayPayment(
                                    $booking,
                                    BookingCustomerPayment::METHOD_STRIPE,
                                    (string) $object->id,
                                    $amountMinor,
                                    $currency,
                                    'Verified via Stripe webhook (' . $eventType . ')',
                                    $event->id
                                );
                            }
                        }
                    }
                    break;

                case 'checkout.session.async_payment_failed':
                case 'checkout.session.expired':
                    if ($object && ! empty($object->id)) {
                        $bookingId = (int) ($object->metadata->booking_id ?? $object->client_reference_id ?? 0);
                        $booking = $bookingId > 0 ? Booking::find($bookingId) : null;
                        if ($booking) {
                            $this->collectionPaymentService->markGatewayPaymentFailed(
                                $booking,
                                BookingCustomerPayment::METHOD_STRIPE,
                                (string) $object->id,
                                $eventType === 'checkout.session.expired'
                                    ? 'Stripe Checkout session expired.'
                                    : 'Stripe payment failed.'
                            );
                        }
                    }
                    break;

                case 'payment_intent.succeeded':
                    if ($object && ! empty($object->id)) {
                        $booking = Booking::where('stripe_payment_intent_id', $object->id)->first();
                        if (! $booking && ! empty($object->metadata->booking_id)) {
                            $booking = Booking::find((int) $object->metadata->booking_id);
                        }
                        if ($booking) {
                            $amountMinor = (int) ($object->amount_received ?? $object->amount ?? 0);
                            $currency = strtoupper((string) ($object->currency ?? $booking->currency ?? 'GBP'));
                            if ($amountMinor > 0) {
                                $this->collectionPaymentService->recordVerifiedGatewayPayment(
                                    $booking,
                                    BookingCustomerPayment::METHOD_STRIPE,
                                    (string) $object->id,
                                    $amountMinor,
                                    $currency,
                                    'Verified via Stripe payment_intent.succeeded',
                                    $event->id
                                );
                            } else {
                                $booking->update([
                                    'payment_status' => 'paid',
                                    'paid_at' => $booking->paid_at ?? now(),
                                    'last_payment_error' => null,
                                ]);
                            }
                        }
                    }
                    break;

                case 'payment_intent.payment_failed':
                    if ($object && ! empty($object->id)) {
                        $reason = $object->last_payment_error->message ?? 'Payment failed.';
                        $booking = Booking::where('stripe_payment_intent_id', $object->id)->first();
                        if ($booking) {
                            $this->collectionPaymentService->markGatewayPaymentFailed(
                                $booking,
                                BookingCustomerPayment::METHOD_STRIPE,
                                (string) $object->id,
                                $reason
                            );
                        }
                    }
                    break;

                case 'payment_intent.requires_action':
                    if ($object && ! empty($object->id)) {
                        Booking::where('stripe_payment_intent_id', $object->id)->update([
                            'payment_status' => 'requires_action',
                            'last_payment_error' => 'Customer authentication (3D Secure) required.',
                        ]);
                    }
                    break;

                case 'payment_intent.processing':
                    if ($object && ! empty($object->id)) {
                        Booking::where('stripe_payment_intent_id', $object->id)->update([
                            'last_payment_error' => null,
                        ]);
                    }
                    break;

                case 'payment_intent.canceled':
                    if ($object && ! empty($object->id)) {
                        $booking = Booking::where('stripe_payment_intent_id', $object->id)->first();
                        if ($booking) {
                            $this->collectionPaymentService->markGatewayPaymentFailed(
                                $booking,
                                BookingCustomerPayment::METHOD_STRIPE,
                                (string) $object->id,
                                'Stripe payment was canceled.'
                            );
                        }
                    }
                    break;

                case 'charge.refunded':
                    if ($object && ! empty($object->payment_intent)) {
                        Booking::where('stripe_payment_intent_id', $object->payment_intent)->update([
                            'payment_status' => 'refunded',
                            'refund_status' => 'succeeded',
                            'refunded_amount_minor' => (int) ($object->amount_refunded ?? 0),
                        ]);
                    }
                    break;
            }

            StripeWebhookEvent::create([
                'stripe_event_id' => $event->id,
                'event_type' => $eventType,
                'payload_summary' => [
                    'object_id' => $object->id ?? null,
                ],
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing error', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Webhook processing error.'], 500);
        }

        return response()->json(['received' => true]);
    }
}
