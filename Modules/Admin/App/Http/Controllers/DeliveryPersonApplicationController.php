<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPersonApplication;
use App\Models\DeliveryPersonApplicationDocument;
use App\Notifications\DeliveryPersonApplicationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonApplicationController extends Controller
{
    public function index(Request $request)
    {
        $counts = [
            'total' => DeliveryPersonApplication::count(),
            'submitted' => DeliveryPersonApplication::where('status', DeliveryPersonApplication::STATUS_SUBMITTED)->count(),
            'under_review' => DeliveryPersonApplication::where('status', DeliveryPersonApplication::STATUS_UNDER_REVIEW)->count(),
            'additional_info_required' => DeliveryPersonApplication::where('status', DeliveryPersonApplication::STATUS_ADDITIONAL_INFO_REQUIRED)->count(),
            'approved' => DeliveryPersonApplication::where('status', DeliveryPersonApplication::STATUS_APPROVED)->count(),
            'rejected' => DeliveryPersonApplication::where('status', DeliveryPersonApplication::STATUS_REJECTED)->count(),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            $query = DeliveryPersonApplication::query()->with('reviewedBy:id,name');

            if ($search = trim((string) $request->query('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('phone_normalized', 'like', "%{$search}%")
                        ->orWhere('postcode', 'like', "%{$search}%");
                });
            }

            if ($status = trim((string) $request->query('status', ''))) {
                if ($status === 'review_queue') {
                    $query->whereIn('status', [
                        DeliveryPersonApplication::STATUS_SUBMITTED,
                        DeliveryPersonApplication::STATUS_UNDER_REVIEW,
                        DeliveryPersonApplication::STATUS_ADDITIONAL_INFO_REQUIRED,
                    ]);
                } else {
                    $query->where('status', $status);
                }
            }

            if ($dateFrom = $request->query('date_from')) {
                $query->whereDate('submitted_at', '>=', $dateFrom);
            }

            if ($dateTo = $request->query('date_to')) {
                $query->whereDate('submitted_at', '<=', $dateTo);
            }

            $sortBy = in_array($request->query('sort_by'), ['submitted_at', 'full_name', 'status', 'reference'], true)
                ? $request->query('sort_by')
                : 'submitted_at';
            $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

            $applications = $query->orderBy($sortBy, $sortDir)->paginate(15);

            return response()->json([
                'success' => true,
                'counts' => $counts,
                'data' => $applications->through(fn (DeliveryPersonApplication $app) => [
                    'id' => $app->id,
                    'reference' => $app->reference,
                    'full_name' => $app->full_name,
                    'email' => $app->email,
                    'phone' => trim($app->phone_country_code . ' ' . $app->phone),
                    'service_area' => $app->service_area ?: ($app->city . ' (' . $app->postcode . ')'),
                    'delivery_method' => ucfirst($app->delivery_method),
                    'submitted_at' => optional($app->submitted_at)->format('d M Y, H:i'),
                    'status' => $app->status,
                    'status_label' => DeliveryPersonApplication::statusLabel($app->status),
                    'reviewed_by' => $app->reviewedBy?->name ?? '—',
                ]),
                'pagination' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'total' => $applications->total(),
                ],
            ]);
        }

        return view('admin::delivery-person-applications.index', compact('counts'));
    }

    public function show(DeliveryPersonApplication $application): JsonResponse
    {
        $application->load(['documents', 'events', 'reviewedBy:id,name', 'approvedDeliveryPerson:id,code,full_name,work_status']);

        $existingMatch = DeliveryPerson::query()
            ->where('email', $application->email)
            ->orWhere('phone_normalized', $application->phone_normalized)
            ->first(['id', 'code', 'full_name', 'work_status']);

        return response()->json([
            'success' => true,
            'application' => [
                'id' => $application->id,
                'reference' => $application->reference,
                'full_name' => $application->full_name,
                'email' => $application->email,
                'phone' => trim($application->phone_country_code . ' ' . $application->phone),
                'date_of_birth' => optional($application->date_of_birth)->format('Y-m-d'),
                'address' => trim(implode(', ', array_filter([
                    $application->address_line_1,
                    $application->address_line_2,
                    $application->city,
                    $application->postcode,
                    $application->country_name,
                ]))),
                'service_area' => $application->service_area,
                'delivery_method' => ucfirst($application->delivery_method),
                'vehicle_type' => $application->vehicle_type,
                'vehicle_registration' => $application->vehicle_registration,
                'driving_licence_number' => $application->driving_licence_number,
                'experience_notes' => $application->experience_notes,
                'status' => $application->status,
                'status_label' => DeliveryPersonApplication::statusLabel($application->status),
                'submitted_at' => optional($application->submitted_at)->format('d M Y, H:i'),
                'reviewed_at' => optional($application->reviewed_at)->format('d M Y, H:i'),
                'reviewed_by' => $application->reviewedBy?->name,
                'decision_notes' => $application->decision_notes,
                'applicant_message' => $application->applicant_message,
                'additional_info_requested' => $application->additional_info_requested,
                'approved_delivery_person' => $application->approvedDeliveryPerson,
                'existing_match' => $existingMatch,
                'documents' => $application->documents->map(fn (DeliveryPersonApplicationDocument $doc) => [
                    'id' => $doc->id,
                    'document_key' => $doc->document_type,
                    'document_type' => ucfirst(str_replace('_', ' ', $doc->document_type)),
                    'original_name' => $doc->original_name,
                    'mime_type' => $doc->mime_type,
                    'file_size' => $doc->file_size,
                    'is_image' => in_array($doc->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true),
                    'is_pdf' => $doc->mime_type === 'application/pdf',
                    'uploaded_at' => optional($doc->uploaded_at)->format('d M Y, H:i'),
                    'preview_url' => route('admin.delivery-person-applications.documents.preview', [$application->id, $doc->id], false),
                    'download_url' => route('admin.delivery-person-applications.documents.download', [$application->id, $doc->id], false),
                ]),
                'events' => $application->events->map(fn ($ev) => [
                    'event_type' => ucfirst(str_replace('_', ' ', $ev->event_type)),
                    'actor_type' => ucfirst($ev->actor_type),
                    'notes' => $ev->notes,
                    'created_at' => optional($ev->created_at)->format('d M Y, H:i'),
                ]),
            ],
        ]);
    }

    public function beginReview(DeliveryPersonApplication $application): JsonResponse
    {
        if ($application->isFinalized()) {
            return response()->json(['success' => false, 'message' => 'This application has already been finalized.'], 422);
        }

        $adminId = Auth::guard('admin')->id();
        $application->update([
            'status' => DeliveryPersonApplication::STATUS_UNDER_REVIEW,
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
        ]);
        $application->recordEvent('review_started', 'admin', $adminId, 'Admin started reviewing the application.');
        $this->notifySafely($application, 'under_review');

        return response()->json(['success' => true, 'message' => 'Application marked as Under Review.']);
    }

    public function requestInfo(Request $request, DeliveryPersonApplication $application): JsonResponse
    {
        if ($application->isFinalized()) {
            return response()->json(['success' => false, 'message' => 'This application has already been finalized.'], 422);
        }

        $validated = $request->validate([
            'additional_info_requested' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $adminId = Auth::guard('admin')->id();
        $application->update([
            'status' => DeliveryPersonApplication::STATUS_ADDITIONAL_INFO_REQUIRED,
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
            'additional_info_requested' => $validated['additional_info_requested'],
            'applicant_message' => $validated['additional_info_requested'],
        ]);
        $application->recordEvent('additional_info_requested', 'admin', $adminId, $validated['additional_info_requested']);
        $this->notifySafely($application, 'additional_info_required', $validated['additional_info_requested']);

        return response()->json(['success' => true, 'message' => 'Additional information requested from applicant.']);
    }

    public function approve(Request $request, DeliveryPersonApplication $application): JsonResponse
    {
        $validated = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $adminId = Auth::guard('admin')->id();

        try {
            $deliveryPerson = DB::transaction(function () use ($application, $validated, $adminId) {
                /** @var DeliveryPersonApplication $locked */
                $locked = DeliveryPersonApplication::query()->lockForUpdate()->findOrFail($application->id);

                if ($locked->status === DeliveryPersonApplication::STATUS_APPROVED || $locked->approved_delivery_person_id !== null) {
                    throw new \DomainException('This application has already been approved.');
                }

                if ($locked->status === DeliveryPersonApplication::STATUS_REJECTED) {
                    throw new \DomainException('A rejected application cannot be approved.');
                }

                $duplicate = DeliveryPerson::query()
                    ->where('email', $locked->email)
                    ->orWhere('phone_normalized', $locked->phone_normalized)
                    ->exists();

                if ($duplicate) {
                    throw new \DomainException('A Delivery Person account with this email or phone already exists.');
                }

                $code = 'DP-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                while (DeliveryPerson::where('code', $code)->exists()) {
                    $code = 'DP-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                }

                $dp = new DeliveryPerson();
                $dp->forceFill([
                    'code' => $code,
                    'full_name' => $locked->full_name,
                    'email' => $locked->email,
                    'phone_country_code' => $locked->phone_country_code,
                    'phone' => $locked->phone,
                    'phone_normalized' => $locked->phone_normalized,
                    'date_of_birth' => $locked->date_of_birth ?? now()->subYears(25)->toDateString(),
                    'address_line_1' => $locked->address_line_1,
                    'address_line_2' => $locked->address_line_2,
                    'city' => $locked->city,
                    'country_code' => $locked->country_code,
                    'country_name' => $locked->country_name,
                    'residential_postcode' => $locked->postcode,
                    'employment_type' => 'contractor',
                    'work_status' => 'active',
                    'availability_status' => 'available',
                    'joining_date' => now()->toDateString(),
                    'vehicle_type' => $locked->vehicle_type ?: $locked->delivery_method,
                    'vehicle_registration' => $locked->vehicle_registration,
                    'license_details' => $locked->driving_licence_number,
                    'portal_access_enabled' => true,
                    'password' => $locked->password,
                    'created_by_admin_id' => $adminId,
                    'updated_by_admin_id' => $adminId,
                ]);
                $dp->save();

                $locked->update([
                    'status' => DeliveryPersonApplication::STATUS_APPROVED,
                    'reviewed_by_admin_id' => $adminId,
                    'reviewed_at' => now(),
                    'decision_notes' => $validated['decision_notes'] ?? null,
                    'applicant_message' => 'Your application has been approved. You can now sign in to the Delivery Person Portal.',
                    'approved_delivery_person_id' => $dp->id,
                ]);

                $locked->recordEvent('approved', 'admin', $adminId, $validated['decision_notes'] ?? 'Application approved and Delivery Person account activated.', [
                    'delivery_person_id' => $dp->id,
                    'delivery_person_code' => $dp->code,
                ]);

                DB::afterCommit(function () use ($locked) {
                    $this->notifySafely($locked, 'approved');
                });

                return $dp;
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Application approved and Delivery Person account (' . $deliveryPerson->code . ') activated.',
        ]);
    }

    public function reject(Request $request, DeliveryPersonApplication $application): JsonResponse
    {
        if ($application->isFinalized()) {
            return response()->json(['success' => false, 'message' => 'This application has already been finalized.'], 422);
        }

        $validated = $request->validate([
            'decision_notes' => ['required', 'string', 'min:5', 'max:2000'],
            'applicant_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $adminId = Auth::guard('admin')->id();
        $publicMsg = $validated['applicant_message'] ?: 'Your application was not approved at this time.';

        $application->update([
            'status' => DeliveryPersonApplication::STATUS_REJECTED,
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
            'decision_notes' => $validated['decision_notes'],
            'applicant_message' => $publicMsg,
        ]);

        $application->recordEvent('rejected', 'admin', $adminId, $validated['decision_notes']);
        $this->notifySafely($application, 'rejected', $publicMsg);

        return response()->json(['success' => true, 'message' => 'Application rejected.']);
    }

    public function previewDocument(DeliveryPersonApplication $application, DeliveryPersonApplicationDocument $document)
    {
        abort_unless($document->application_id === $application->id, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->storage_path), 404);

        $allowedInlineMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $mime = strtolower((string) $document->mime_type);
        abort_unless(in_array($mime, $allowedInlineMimes, true), 415);

        $stream = Storage::disk($document->disk)->readStream($document->storage_path);
        abort_if($stream === false, 404);

        $safeName = str_replace(['"', '\\', '/'], '_', (string) $document->original_name);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $safeName . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadDocument(DeliveryPersonApplication $application, DeliveryPersonApplicationDocument $document)
    {
        abort_unless($document->application_id === $application->id, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->storage_path), 404);

        $application->recordEvent('document_accessed', 'admin', Auth::guard('admin')->id(), 'Downloaded ' . $document->document_type);

        return Storage::disk($document->disk)->download($document->storage_path, $document->original_name);
    }

    private function notifySafely(DeliveryPersonApplication $application, string $eventType, ?string $customMessage = null): void
    {
        try {
            Notification::route('mail', $application->email)
                ->notify(new DeliveryPersonApplicationNotification($application, $eventType, $customMessage));
        } catch (\Throwable $e) {
            Log::warning('Admin DeliveryPersonApplication notification failed', [
                'application_id' => $application->id,
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
