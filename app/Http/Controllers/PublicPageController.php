<?php

namespace App\Http\Controllers;

use App\Mail\ContactEnquiryNotificationMail;
use App\Models\Booking;
use App\Models\ContactEnquiry;
use App\Services\AboutPageContentService;
use App\Services\ContactPageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function __construct(
        protected AboutPageContentService $aboutCms,
        protected ContactPageContentService $contactCms
    ) {}

    public function about(Request $request): View
    {
        $isPreview = $request->query('preview') === 'draft'
            && Auth::guard('admin')->check()
            && Auth::guard('admin')->user()?->can('manage-settings', 'admin');

        $payload = $this->aboutCms->getPublicPayload($isPreview);
        $sections = collect($payload['sections'] ?? [])->sortBy(fn ($s) => (int) ($s['order'] ?? 99))->values();

        return view('pages.about', [
            'payload' => $payload,
            'sections' => $sections,
            'isPreviewDraft' => $isPreview,
            'cms' => $this->aboutCms,
        ]);
    }

    public function contact(Request $request): View
    {
        $isPreview = $request->query('preview') === 'draft'
            && Auth::guard('admin')->check()
            && Auth::guard('admin')->user()?->can('manage-settings', 'admin');

        $payload = $this->contactCms->getPublicPayload($isPreview);
        $sections = collect($payload['sections'] ?? [])->sortBy(fn ($s) => (int) ($s['order'] ?? 99))->values();

        return view('pages.contact', [
            'payload' => $payload,
            'sections' => $sections,
            'isPreviewDraft' => $isPreview,
            'cms' => $this->contactCms,
        ]);
    }

    public function submitEnquiry(Request $request): JsonResponse
    {
        // Honeypot spam trap: bots filling hidden website_url are silently rejected
        if (filled($request->input('website_url'))) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Thank you! Your enquiry has been received.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:140'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'min:3', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'booking_reference' => ['nullable', 'string', 'max:60'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ]);

        $idempotencyKey = ! empty($validated['idempotency_key']) ? trim((string) $validated['idempotency_key']) : null;
        if ($idempotencyKey !== null) {
            $existing = ContactEnquiry::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return response()->json([
                    'status' => 'ok',
                    'reference' => $existing->reference,
                    'message' => 'Thank you! Your enquiry has been received.',
                ]);
            }
        }

        $bookingRef = ! empty($validated['booking_reference']) ? strtoupper(trim((string) $validated['booking_reference'])) : null;
        $bookingId = null;
        if ($bookingRef !== null) {
            // Link internally for Admin review; never expose booking data back to the public response
            $bookingId = Booking::query()->where('reference', $bookingRef)->value('id');
        }

        $now = now();
        $enquiry = ContactEnquiry::create([
            'reference' => 'ENQ-' . $now->format('Y') . '-' . strtoupper(Str::random(6)),
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => ! empty($validated['phone']) ? trim($validated['phone']) : null,
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'booking_reference' => $bookingRef,
            'booking_id' => $bookingId,
            'status' => ContactEnquiry::STATUS_NEW,
            'idempotency_key' => $idempotencyKey,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'email_notification_status' => 'pending',
            'submitted_at' => $now,
        ]);

        $enquiry->reference = 'ENQ-' . $now->format('Y') . '-' . str_pad((string) $enquiry->id, 6, '0', STR_PAD_LEFT);
        $enquiry->save();

        // Notify configured support/admin email safely without discarding enquiry on mail error
        $contactPayload = $this->contactCms->getPublicPayload(false);
        $supportEmail = trim((string) ($contactPayload['contact_info']['support_email'] ?? ''));
        if ($supportEmail === '' || ! filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            $supportEmail = (string) config('mail.from.address', '');
        }

        if ($supportEmail !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($supportEmail)->send(new ContactEnquiryNotificationMail($enquiry));
                $enquiry->update(['email_notification_status' => 'sent']);
            } catch (\Throwable $e) {
                Log::warning('Contact enquiry email notification failed', [
                    'enquiry_id' => $enquiry->id,
                    'error' => $e->getMessage(),
                ]);
                $enquiry->update([
                    'email_notification_status' => 'failed',
                    'email_notification_error' => Str::limit($e->getMessage(), 250, ''),
                ]);
            }
        } else {
            $enquiry->update(['email_notification_status' => 'skipped']);
        }

        $successMessage = trim((string) ($contactPayload['form_config']['success_message'] ?? ''));

        return response()->json([
            'status' => 'ok',
            'reference' => $enquiry->reference,
            'message' => $successMessage !== '' ? $successMessage : 'Thank you! Your enquiry has been received.',
        ], 201);
    }
}
