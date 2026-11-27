<?php

namespace App\Http\Controllers\DeliveryPersonPortal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LaundryTag;
use App\Services\BookingTagAssignmentService;
use App\Services\LaundryTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonTagController extends Controller
{
    public function __construct(
        private readonly LaundryTagService $tagService,
        private readonly BookingTagAssignmentService $bookingTagService,
    ) {}

    private function person(): DeliveryPerson
    {
        /** @var DeliveryPerson $person */
        $person = Auth::guard('delivery_person')->user();
        return $person;
    }

    /**
     * Delivery Person personal tag inventory page & AJAX filter endpoint.
     */
    public function index(Request $request): View|JsonResponse
    {
        $person = $this->person();

        $query = LaundryTag::query()
            ->where('current_delivery_person_id', $person->id)
            ->with(['currentBooking', 'activeBookingAssignment'])
            ->orderByDesc('allocated_at')
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('search', ''))) {
            $upper = strtoupper($search);
            $query->where(function ($q) use ($upper, $search) {
                $q->where('tag_code', 'like', "%{$upper}%")
                    ->orWhereHas('currentBooking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%"));
            });
        }

        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status', $status);
        }

        $tags = $query->paginate(20)->withQueryString();

        $baseCounts = LaundryTag::where('current_delivery_person_id', $person->id)
            ->selectRaw('status, COUNT(*) as agg')
            ->groupBy('status')
            ->pluck('agg', 'status');

        $summary = [
            'total_allocated' => (int) $baseCounts->sum(),
            'available_to_use' => (int) ($baseCounts[LaundryTag::STATUS_ALLOCATED] ?? 0),
            'active_on_bookings' => (int) ($baseCounts[LaundryTag::STATUS_ASSIGNED] ?? 0)
                + (int) ($baseCounts[LaundryTag::STATUS_AT_STORE] ?? 0)
                + (int) ($baseCounts[LaundryTag::STATUS_IN_FINAL_DELIVERY] ?? 0),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'table_html' => view('delivery-person-portal.tags.partials.tags-table', compact('tags'))->render(),
            ]);
        }

        return view('delivery-person-portal.tags.index', compact('person', 'tags', 'summary'));
    }

    /**
     * Verify & assign a physical tag (manual code or scanned QR payload) to a Collection Booking.
     */
    public function assignToBooking(Request $request, Booking $booking): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $person = $this->person();

        $validated = $request->validate([
            'tag_code' => ['required', 'string', 'max:120'],
            'package_label' => ['nullable', 'string', 'max:60'],
            'method' => ['nullable', 'in:manual,qr'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $assignment = $this->bookingTagService->verifyAndAssignToCollectionBooking(
                $booking,
                $person,
                $validated['tag_code'],
                $validated['package_label'] ?? null,
                $validated['method'] ?? null,
                $validated['idempotency_key'] ?? null
            );
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to assign laundry tag.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $booking->load('activeTagAssignments.tag');
        $msg = "Tag {$assignment->tag->tag_code} verified & attached ({$assignment->package_label}).";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'tags_html' => view('delivery-person-portal.bookings.partials.assigned-tags-list', ['booking' => $booking])->render(),
                'tag_count' => $booking->activeTagAssignments->count(),
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Unassign a tag before Store handover acceptance.
     */
    public function unassignFromBooking(Request $request, Booking $booking, LaundryTag $tag): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $person = $this->person();

        try {
            $this->bookingTagService->unassignFromCollectionBooking(
                $booking,
                $tag,
                $person,
                (string) $request->input('reason', 'Removed before store handover confirmation')
            );
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to remove tag.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $booking->load('activeTagAssignments.tag');
        $msg = "Removed tag {$tag->tag_code} from booking.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'tags_html' => view('delivery-person-portal.bookings.partials.assigned-tags-list', ['booking' => $booking])->render(),
                'tag_count' => $booking->activeTagAssignments->count(),
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Allow Delivery Person to report an unused allocated tag in their possession as Lost or Damaged.
     */
    public function reportException(Request $request, LaundryTag $tag): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $person = $this->person();
        if ((int) $tag->current_delivery_person_id !== (int) $person->id) {
            abort(403, 'You can only report exceptions on tags allocated to you.');
        }

        if ($tag->isActivelyAttached()) {
            $msg = "Tag {$tag->tag_code} is attached to a booking. Unassign it or ask Store/Admin first.";
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['tag' => $msg]);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:lost,damaged'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $updated = $this->tagService->transitionExceptionOrRetire(
                $tag,
                $validated['status'],
                $validated['reason'],
                'delivery_person',
                $person->id,
                $person->full_name,
                false
            );
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to report tag exception.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors($e->errors());
        }

        $msg = "Reported tag {$updated->tag_code} as {$updated->status_label}.";

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }
}
