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

    public function index(Request $request): View
    {
        $verifiedRefs = session('verified_order_references', []);
        if (! is_array($verifiedRefs)) {
            $verifiedRefs = [];
        }

        if ($lastRef = session('last_confirmed_booking_reference')) {
            $verifiedRefs[] = $lastRef;
        }
        $verifiedRefs = array_values(array_unique(array_filter($verifiedRefs)));

        $bookings = Booking::query()
            ->whereNotNull('confirmed_at')
            ->where(function ($query) use ($request, $verifiedRefs) {
                if ($request->user()) {
                    $query->where('user_id', $request->user()->id);
                    if ($verifiedRefs !== []) {
                        $query->orWhereIn('reference', $verifiedRefs);
                    }
                } elseif ($verifiedRefs !== []) {
                    $query->whereIn('reference', $verifiedRefs);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderByDesc('confirmed_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('orders.index', [
            'bookings' => $bookings,
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
