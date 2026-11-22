<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PayPalPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalPaymentController extends Controller
{
    public function __construct(protected PayPalPaymentService $paypal) {}

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
        $orderId = (string) ($request->query('token') ?: $booking->paypal_order_id);
        if ($orderId === '') {
            return redirect()->route('booking.confirm')->with('error', 'Missing PayPal order token.');
        }

        try {
            $capture = $this->paypal->captureOrder($orderId);

            if (strtoupper((string) ($capture['status'] ?? '')) === 'COMPLETED') {
                $booking->update([
                    'paypal_order_id' => $capture['order_id'],
                    'paypal_capture_id' => $capture['capture_id'],
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => $booking->payment_status,
                    'order_id' => $capture['order_id'],
                    'capture_id' => $capture['capture_id'],
                ]);
            }

            return redirect()->route('booking.confirm')->with('success', 'PayPal payment completed.');
        } catch (\Throwable $e) {
            Log::error('PayPal capture failed', [
                'booking_id' => $booking->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to capture PayPal payment.'], 500);
            }

            return redirect()->route('booking.confirm')->with('error', 'Unable to capture PayPal payment.');
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
                    $booking->update([
                        'paypal_capture_id' => (string) ($resource['id'] ?? $booking->paypal_capture_id),
                        'payment_status' => 'paid',
                        'paid_at' => $booking->paid_at ?? now(),
                    ]);
                } elseif (in_array($eventType, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED'], true)) {
                    $booking->update([
                        'payment_status' => 'failed',
                    ]);
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
