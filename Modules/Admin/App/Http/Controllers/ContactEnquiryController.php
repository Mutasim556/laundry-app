<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactEnquiryController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));

        $query = ContactEnquiry::query()->orderByDesc('id');

        if ($status && in_array($status, [
            ContactEnquiry::STATUS_NEW,
            ContactEnquiry::STATUS_READ,
            ContactEnquiry::STATUS_IN_PROGRESS,
            ContactEnquiry::STATUS_RESOLVED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $enquiries = $query->paginate(20)->withQueryString();

        $kpis = [
            'total' => ContactEnquiry::count(),
            'new' => ContactEnquiry::where('status', ContactEnquiry::STATUS_NEW)->count(),
            'in_progress' => ContactEnquiry::where('status', ContactEnquiry::STATUS_IN_PROGRESS)->count(),
            'resolved' => ContactEnquiry::where('status', ContactEnquiry::STATUS_RESOLVED)->count(),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'kpis' => $kpis,
                'table_html' => view('admin::website-manager.partials.enquiries-table', ['enquiries' => $enquiries])->render(),
            ]);
        }

        return view('admin::website-manager.enquiries', [
            'enquiries' => $enquiries,
            'kpis' => $kpis,
            'activeStatus' => $status,
            'search' => $search,
        ]);
    }

    public function show(ContactEnquiry $enquiry): JsonResponse
    {
        if ($enquiry->status === ContactEnquiry::STATUS_NEW) {
            $enquiry->update([
                'status' => ContactEnquiry::STATUS_READ,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'enquiry' => [
                'id' => $enquiry->id,
                'reference' => $enquiry->reference,
                'name' => $enquiry->name,
                'email' => $enquiry->email,
                'phone' => $enquiry->phone,
                'subject' => $enquiry->subject,
                'message' => $enquiry->message,
                'booking_reference' => $enquiry->booking_reference,
                'status' => $enquiry->status,
                'status_meta' => $enquiry->statusMeta(),
                'internal_notes' => $enquiry->internal_notes,
                'email_notification_status' => $enquiry->email_notification_status,
                'submitted_at' => $enquiry->submitted_at?->format('d M Y, H:i'),
                'read_at' => $enquiry->read_at?->format('d M Y, H:i'),
                'resolved_at' => $enquiry->resolved_at?->format('d M Y, H:i'),
            ],
        ]);
    }

    public function updateStatus(Request $request, ContactEnquiry $enquiry): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                ContactEnquiry::STATUS_NEW,
                ContactEnquiry::STATUS_READ,
                ContactEnquiry::STATUS_IN_PROGRESS,
                ContactEnquiry::STATUS_RESOLVED,
            ])],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $updates = [
            'status' => $validated['status'],
            'internal_notes' => array_key_exists('internal_notes', $validated) ? $validated['internal_notes'] : $enquiry->internal_notes,
            'assigned_admin_id' => (int) Auth::guard('admin')->id(),
        ];

        if ($validated['status'] === ContactEnquiry::STATUS_READ && ! $enquiry->read_at) {
            $updates['read_at'] = now();
        }
        if ($validated['status'] === ContactEnquiry::STATUS_RESOLVED && ! $enquiry->resolved_at) {
            $updates['resolved_at'] = now();
        }

        $enquiry->update($updates);

        return response()->json([
            'status' => 'ok',
            'message' => "Enquiry {$enquiry->reference} updated to {$enquiry->statusMeta()['label']}.",
            'enquiry_status' => $enquiry->status,
        ]);
    }
}
