<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Services\CollectionPaymentService;
use App\Services\PayPalPaymentService;
use App\Services\StoreAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalPaymentController extends Controller
{
    public function __construct(
        protected PayPalPaymentService $paypal,
        protected CollectionPaymentService $collectionPaymentService
    ) {}

    public function createOrder(Request $request, Booking $booking): JsonResponse
    {
        if (! $this->paypal->isActive()) {
            return response()->json([
                'message' => 'PayPal is currently unavailable.',
            ], 422);
        }

        if ($booking->payment_gateway !== 'paypal') {
            return response()->json([
                'message' => 'This booking is not configured for PayPal payment.',
            ], 422);
        }

        try {
            $result = $this->paypal->createOrder(
                $booking,
                route('booking.paypal.capture', ['booking' => $booking->id]),
                route('booking.confirm')
            );

            if (! empty($result['order_id'])) {
                $booking->update([
                    'paypal_order_id' => $result['order_id'],
                ]);
            }

            return response()->json([
                'order_id' => $result['order_id'],
                'approve_url' => $result['approve_url'],
                'status' => $result['status'],
            ]);
        } catch (\Throwable $e) {
            Log::error('PayPal order creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to initiate PayPal payment at this time.',
            ], 500);
        }
    }

    public function captureReturn(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $orderId = trim((string) ($request->query('token') ?: $booking->paypal_order_id));
        if ($orderId === '') {
            return redirect()->route('orders.show', $booking->reference)
                ->with('error', 'Missing PayPal order token.');
        }

        if (! empty($booking->paypal_order_id) && $booking->paypal_order_id !== $orderId) {
            return redirect()->route('orders.show', $booking->reference)
                ->with('error', 'PayPal order token does not match this booking.');
        }

        $alreadyConfirmed = BookingCustomerPayment::query()
            ->where('booking_id', $booking->id)
            ->where('payment_method', BookingCustomerPayment::METHOD_PAYPAL)
            ->where('external_reference', $orderId)
            ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
            ->exists();

        if ($alreadyConfirmed) {
            if ($request->expectsJson()) {
                return response()->json(['status' => $booking->payment_status, 'order_id' => $orderId]);
            }

            return redirect()->route('orders.show', $booking->reference)
                ->with('success', 'PayPal payment already confirmed.');
        }

        try {
            $capture = $this->paypal->captureOrder($orderId);

            if (strtoupper((string) ($capture['status'] ?? '')) === 'COMPLETED') {
                $booking->forceFill([
                    'paypal_order_id' => $capture['order_id'],
                    'paypal_capture_id' => $capture['capture_id'],
                ])->save();

                $amountMinor = ! empty($capture['amount_value'])
                    ? StoreAccountService::decimalToMinor($capture['amount_value'])
                    : (int) ($booking->final_amount_minor ?? $booking->estimated_amount_minor ?? 0);
                $currency = strtoupper((string) ($capture['currency_code'] ?: $booking->currency ?: 'GBP'));

                $this->collectionPaymentService->recordVerifiedGatewayPayment(
                    $booking,
                    BookingCustomerPayment::METHOD_PAYPAL,
                    $capture['order_id'],
                    $amountMinor,
                    $currency,
                    'PayPal capture ID: ' . ($capture['capture_id'] ?? 'n/a')
                );
                $booking->refresh();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => $booking->payment_status,
                    'order_id' => $capture['order_id'],
                    'capture_id' => $capture['capture_id'],
                ]);
            }

            return redirect()->route('orders.show', $booking->reference)
                ->with('success', 'PayPal payment completed.');
        } catch (\Throwable $e) {
            Log::error('PayPal capture failed', [
                'booking_id' => $booking->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to capture PayPal payment.'], 500);
            }

            return redirect()->route('orders.show', $booking->reference)
                ->with('error', 'Unable to capture PayPal payment.');
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $headers = array_change_key_case($request->headers->all(), CASE_LOWER);
        $flatHeaders = array_map(static fn ($v) => is_array($v) ? ($v[0] ?? '') : (string) $v, $headers);

        if (! $this->paypal->verifyWebhookSignature($flatHeaders, $payload)) {
            return response()->json(['message' => 'Invalid PayPal webhook signature.'], 400);
        }

        $eventType = (string) ($payload['event_type'] ?? '');
        $resource = $payload['resource'] ?? [];
        $orderId = (string) data_get($resource, 'supplementary_data.related_ids.order_id', data_get($resource, 'id', ''));

        if ($orderId !== '') {
            $booking = Booking::where('paypal_order_id', $orderId)->first();
            if ($booking) {
                if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                    $captureId = (string) ($resource['id'] ?? $booking->paypal_capture_id ?? '');
                    if ($captureId !== '') {
                        $booking->forceFill(['paypal_capture_id' => $captureId])->save();
                    }
                    $amountVal = data_get($resource, 'amount.value');
                    $amountMinor = $amountVal !== null
                        ? StoreAccountService::decimalToMinor((string) $amountVal)
                        : (int) ($booking->final_amount_minor ?? $booking->estimated_amount_minor ?? 0);
                    $currency = strtoupper((string) data_get($resource, 'amount.currency_code', $booking->currency ?: 'GBP'));

                    if ($amountMinor > 0) {
                        $this->collectionPaymentService->recordVerifiedGatewayPayment(
                            $booking,
                            BookingCustomerPayment::METHOD_PAYPAL,
                            $orderId,
                            $amountMinor,
                            $currency,
                            'Verified via PayPal webhook (capture ' . $captureId . ')',
                            (string) ($payload['id'] ?? '')
                        );
                    }
                } elseif (in_array($eventType, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED'], true)) {
                    $this->collectionPaymentService->markGatewayPaymentFailed(
                        $booking,
                        BookingCustomerPayment::METHOD_PAYPAL,
                        $orderId,
                        'PayPal capture ' . strtolower(str_replace('PAYMENT.CAPTURE.', '', $eventType))
                    );
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
