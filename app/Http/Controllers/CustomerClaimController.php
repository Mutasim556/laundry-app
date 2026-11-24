<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ClaimEvidence;
use App\Services\ClaimWorkflowService;
use App\Services\StoreAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerClaimController extends Controller
{
    public function __construct(
        protected ClaimWorkflowService $claimService
    ) {}

    protected function canAccessBooking(Request $request, Booking $booking): bool
    {
        if (Auth::check() && (int) $booking->user_id === (int) Auth::id()) {
            return true;
        }

        if ($request->session()->get('last_booking_reference') === $booking->reference) {
            return true;
        }

        $claimed = (array) $request->session()->get('verified_booking_references', []);

        return in_array($booking->reference, $claimed, true);
    }

    public function store(Request $request, string $reference): JsonResponse
    {
        $booking = Booking::query()->where('reference', $reference)->firstOrFail();
        if (! $this->canAccessBooking($request, $booking)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this booking.'], 403);
        }

        $validated = $request->validate([
            'claim_type' => ['required', 'string', 'in:cancellation_refund,rewash,damage_missing'],
            'reason_code' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
            'preferred_resolution' => ['nullable', 'string', 'max:60'],
            'requested_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'affected_quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'operational_item_ids' => ['nullable', 'array'],
            'operational_item_ids.*' => ['integer'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $requestedMinor = isset($validated['requested_amount']) && $validated['requested_amount'] !== ''
            ? StoreAccountService::decimalToMinor($validated['requested_amount'])
            : 0;

        try {
            $claim = $this->claimService->submitClaim(
                $booking,
                [
                    'claim_type' => $validated['claim_type'],
                    'reason_code' => $validated['reason_code'],
                    'description' => $validated['description'],
                    'preferred_resolution' => $validated['preferred_resolution'] ?? null,
                    'requested_amount_minor' => $requestedMinor,
                    'affected_quantity' => $validated['affected_quantity'] ?? 1,
                    'operational_item_ids' => $validated['operational_item_ids'] ?? [],
                ],
                $request->file('evidence', []),
                Auth::id(),
                $request->ip()
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Unable to submit request.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Request {$claim->case_reference} submitted successfully.",
            'claim' => [
                'id' => $claim->id,
                'case_reference' => $claim->case_reference,
                'claim_type' => $claim->claim_type,
                'type_label' => $claim->type_label,
                'status' => $claim->status,
                'status_label' => $claim->status_label,
                'created_at' => $claim->created_at?->format('d M Y, H:i'),
            ],
        ]);
    }

    public function downloadEvidence(Request $request, ClaimEvidence $evidence): StreamedResponse
    {
        $evidence->loadMissing('claim.booking');
        $booking = $evidence->claim?->booking;
        if (! $booking) {
            abort(404);
        }

        if (! $this->canAccessBooking($request, $booking)) {
            abort(403, 'You are not authorized to view this evidence.');
        }

        $disk = $evidence->disk ?: 'local';
        if (! Storage::disk($disk)->exists($evidence->file_path)) {
            abort(404, 'Evidence file not found.');
        }

        return Storage::disk($disk)->download($evidence->file_path, $evidence->original_name);
    }
}
