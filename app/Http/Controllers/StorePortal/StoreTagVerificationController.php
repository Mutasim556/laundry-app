<?php

namespace App\Http\Controllers\StorePortal;

use App\Http\Controllers\Controller;
use App\Models\LaundryTagBookingAssignment;
use App\Models\StoreUser;
use App\Services\StoreOperationalStatusService;
use App\Services\StoreTagVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StoreTagVerificationController extends Controller
{
    public function __construct(
        private readonly StoreTagVerificationService $verificationService,
    ) {}

    private function storeUser(): StoreUser
    {
        /** @var StoreUser $user */
        $user = Auth::guard('store')->user();
        return $user;
    }

    public function index(Request $request): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $prefillCode = trim((string) $request->input('tag_code', ''));

        return view('store-portal.tag-verification.index', compact('storeUser', 'store', 'prefillCode'));
    }

    public function verify(Request $request): JsonResponse
    {
        $storeUser = $this->storeUser();

        $validated = $request->validate([
            'tag_code' => ['required', 'string', 'max:120'],
            'method' => ['nullable', 'in:manual,qr'],
        ]);

        try {
            $result = $this->verificationService->lookupAndVerifyForStore(
                $storeUser,
                $validated['tag_code'],
                $validated['method'] ?? null
            );
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Tag verification failed.';
            return response()->json([
                'success' => false,
                'message' => $msg,
                'errors' => $e->errors(),
            ], 422);
        }

        $booking = $result['booking'];
        $verifiedAssignment = $result['verified_assignment'];
        $progress = $result['progress'];
        $storeStatus = app(StoreOperationalStatusService::class)->getStatusForBooking($booking);

        return response()->json([
            'success' => true,
            'message' => "Tag {$verifiedAssignment->tag->tag_code} verified for booking {$booking->reference}.",
            'progress' => $progress,
            'booking_panel_html' => view('store-portal.tag-verification.partials.booking-panel', [
                'booking' => $booking,
                'verifiedAssignment' => $verifiedAssignment,
                'progress' => $progress,
                'storeStatus' => $storeStatus,
                'store' => $storeUser->store,
            ])->render(),
        ]);
    }

    public function completeTag(Request $request, LaundryTagBookingAssignment $assignment): JsonResponse
    {
        $storeUser = $this->storeUser();

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->verificationService->markTagProcessingCompleted(
                $storeUser,
                $assignment,
                $validated['notes'] ?? null,
                $request->ip()
            );
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to mark tag as completed.';
            return response()->json([
                'success' => false,
                'message' => $msg,
                'errors' => $e->errors(),
            ], 422);
        }

        $booking = $result['booking'];
        $progress = $result['progress'];
        $storeStatus = app(StoreOperationalStatusService::class)->getStatusForBooking($booking);

        $msg = $result['auto_store_complete_message']
            ?: "Marked tag {$result['assignment']->tag->tag_code} completed ({$progress['completed']}/{$progress['total']}).";

        return response()->json([
            'success' => true,
            'message' => $msg,
            'progress' => $progress,
            'auto_store_complete_status' => $result['auto_store_complete_status'],
            'auto_store_complete_message' => $result['auto_store_complete_message'],
            'booking_panel_html' => view('store-portal.tag-verification.partials.booking-panel', [
                'booking' => $booking,
                'verifiedAssignment' => $result['assignment'],
                'progress' => $progress,
                'storeStatus' => $storeStatus,
                'store' => $storeUser->store,
            ])->render(),
        ]);
    }
}
