<?php

namespace App\Http\Controllers;

use App\Models\DeliveryPersonApplication;
use App\Models\DeliveryPersonApplicationDocument;
use App\Notifications\DeliveryPersonApplicationNotification;
use App\Services\DeliveryPersonJoinPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\Models\DeliveryPerson;

class PublicDeliveryPersonRecruitmentController extends Controller
{
    public function __construct(
        protected DeliveryPersonJoinPageService $cms
    ) {}

    public function index(Request $request)
    {
        $isPreviewDraft = $request->query('preview') === 'draft'
            && Auth::guard('admin')->check()
            && Auth::guard('admin')->user()?->can('manage-settings', 'admin');

        $payload = $this->cms->getPublicPayload($isPreviewDraft);

        if (! ($payload['general']['page_enabled'] ?? true) && ! $isPreviewDraft) {
            abort(404);
        }

        return view('public.join-delivery-person', [
            'payload' => $payload,
            'cms' => $this->cms,
            'isPreviewDraft' => $isPreviewDraft,
            'recruitmentEnabled' => (bool) ($payload['general']['applications_open'] ?? true),
            'deliveryMethods' => [
                'walking' => 'Walking',
                'bicycle' => 'Bicycle',
                'motorbike' => 'Motorbike / Scooter',
                'car' => 'Car',
                'van' => 'Van',
            ],
            'countries' => [
                'GB' => 'United Kingdom',
                'BD' => 'Bangladesh',
                'US' => 'United States',
                'CA' => 'Canada',
                'AU' => 'Australia',
                'IN' => 'India',
                'PK' => 'Pakistan',
                'AE' => 'United Arab Emirates',
                'SA' => 'Saudi Arabia',
                'DE' => 'Germany',
                'FR' => 'France',
                'IE' => 'Ireland',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->cms->getPublicPayload(false);
        if (! ($payload['general']['page_enabled'] ?? true) || ! ($payload['general']['applications_open'] ?? true)) {
            return response()->json([
                'success' => false,
                'message' => $payload['general']['closed_message'] ?? 'Delivery Person applications are currently closed.',
            ], 422);
        }

        $docRules = [
            'profile_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096'],
            'identity_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
            'driving_licence_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
            'vehicle_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
        ];
        $deliveryMethod = (string) $request->input('delivery_method', 'bicycle');
        $isMotor = in_array($deliveryMethod, ['motorbike', 'car', 'van'], true);
        foreach (($payload['documents'] ?? []) as $docCfg) {
            $k = $docCfg['key'] ?? '';
            if (! isset($docRules[$k])) {
                continue;
            }
            $maxKb = min(5, max(1, (int) ($docCfg['max_mb'] ?? 5))) * 1024;
            $docRules[$k][4] = 'max:' . $maxKb;
            $applicable = ! ($docCfg['motor_only'] ?? false) || $isMotor;
            if (($docCfg['visible'] ?? true) && ($docCfg['required'] ?? false) && $applicable) {
                $docRules[$k][0] = 'required';
            }
        }

        $validated = $request->validate(array_merge([
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'phone_country_code' => ['nullable', 'string', 'max:10', 'regex:/^\+?[0-9]{1,4}$/'],
            'phone_iso_code' => ['nullable', 'string', 'size:2'],
            'phone' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:30'],
            'country_code' => ['required', 'string', 'max:5'],
            'service_area' => ['nullable', 'string', 'max:150'],
            'delivery_method' => ['required', 'in:walking,bicycle,motorbike,car,van'],
            'vehicle_type' => ['nullable', 'string', 'max:60'],
            'vehicle_registration' => ['nullable', 'string', 'max:40'],
            'driving_licence_number' => ['nullable', 'string', 'max:60'],
            'experience_notes' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'consent_accepted' => ['accepted'],
        ], $docRules), [
            'date_of_birth.before' => 'Date of birth cannot be in the future.',
            'profile_photo.mimes' => 'Please select a supported image format (JPG, JPEG, PNG, or WebP).',
            'profile_photo.max' => 'This file exceeds the allowed 4 MB upload size.',
            'identity_document.mimes' => 'Please select a supported document format (JPG, PNG, or PDF).',
            'identity_document.max' => 'This file exceeds the allowed 5 MB upload size.',
            'driving_licence_document.mimes' => 'Please select a supported document format (JPG, PNG, or PDF).',
            'driving_licence_document.max' => 'This file exceeds the allowed 5 MB upload size.',
            'vehicle_document.mimes' => 'Please select a supported document format (JPG, PNG, or PDF).',
            'vehicle_document.max' => 'This file exceeds the allowed 5 MB upload size.',
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $phoneCountryCode = trim((string) ($validated['phone_country_code'] ?? '+44'));
        if ($phoneCountryCode !== '' && ! str_starts_with($phoneCountryCode, '+')) {
            $phoneCountryCode = '+' . $phoneCountryCode;
        }
        $phoneIso = strtoupper((string) ($validated['phone_iso_code'] ?? $validated['country_code']));
        $normalized = $this->normalizeInternationalPhone($phoneCountryCode, $validated['phone'], $phoneIso);
        if ($normalized === null) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid mobile number for the selected country.',
                'errors' => [
                    'phone' => ['Please enter a valid mobile number for the selected country.'],
                ],
            ], 422);
        }
        $phoneNormalized = $normalized['digits'];

        // Prevent duplicate registration without enumerating existing accounts
        $existingPerson = DeliveryPerson::query()
            ->where('email', $email)
            ->orWhere('phone_normalized', $phoneNormalized)
            ->exists();

        $existingPendingApp = DeliveryPersonApplication::query()
            ->where(function ($q) use ($email, $phoneNormalized) {
                $q->where('email', $email)->orWhere('phone_normalized', $phoneNormalized);
            })
            ->whereIn('status', [
                DeliveryPersonApplication::STATUS_SUBMITTED,
                DeliveryPersonApplication::STATUS_UNDER_REVIEW,
                DeliveryPersonApplication::STATUS_ADDITIONAL_INFO_REQUIRED,
                DeliveryPersonApplication::STATUS_APPROVED,
            ])
            ->exists();

        if ($existingPerson || $existingPendingApp) {
            return response()->json([
                'success' => false,
                'message' => 'We could not submit a new application with these details. If you already applied or have an account, please check your application status or sign in.',
            ], 422);
        }

        $countryNames = [
            'GB' => 'United Kingdom',
            'BD' => 'Bangladesh',
            'US' => 'United States',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'IN' => 'India',
            'PK' => 'Pakistan',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IE' => 'Ireland',
        ];
        $countryCode = strtoupper($validated['country_code']);
        $rawTrackingToken = Str::random(32);

        $application = DB::transaction(function () use ($request, $validated, $email, $phoneCountryCode, $phoneNormalized, $countryCode, $countryNames, $rawTrackingToken) {
            $reference = DeliveryPersonApplication::generateReference();

            $app = DeliveryPersonApplication::create([
                'reference' => $reference,
                'tracking_token_hash' => hash('sha256', $rawTrackingToken),
                'full_name' => trim($validated['full_name']),
                'email' => $email,
                'phone_country_code' => $phoneCountryCode,
                'phone' => trim($validated['phone']),
                'phone_normalized' => $phoneNormalized,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address_line_1' => trim($validated['address_line_1']),
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => trim($validated['city']),
                'postcode' => strtoupper(trim($validated['postcode'])),
                'country_code' => $countryCode,
                'country_name' => $countryNames[$countryCode] ?? $countryCode,
                'service_area' => $validated['service_area'] ?? null,
                'delivery_method' => $validated['delivery_method'],
                'vehicle_type' => $validated['vehicle_type'] ?? null,
                'vehicle_registration' => isset($validated['vehicle_registration']) ? strtoupper(trim($validated['vehicle_registration'])) : null,
                'driving_licence_number' => $validated['driving_licence_number'] ?? null,
                'experience_notes' => $validated['experience_notes'] ?? null,
                'password' => $validated['password'],
                'consent_accepted' => true,
                'status' => DeliveryPersonApplication::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ]);

            $docFields = [
                'profile_photo' => 'profile_photo',
                'identity_document' => 'identity',
                'driving_licence_document' => 'driving_licence',
                'vehicle_document' => 'vehicle_insurance',
            ];

            foreach ($docFields as $input => $docType) {
                $uploadedFile = $request->file($input);
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $this->storePrivateDocument($app, $uploadedFile, $docType);
                }
            }

            $app->recordEvent('submitted', 'applicant', null, 'Application submitted via public recruitment portal.');

            return $app;
        });

        $this->notifySafely($application, 'submitted');

        return response()->json([
            'success' => true,
            'message' => 'Your application has been received and is awaiting Admin verification. You will be notified when its status changes.',
            'reference' => $application->reference,
            'tracking_token' => $rawTrackingToken,
            'status_url' => route('delivery-person.application-status', [
                'ref' => $application->reference,
            ]),
        ], 201);
    }

    public function statusPage(Request $request)
    {
        return view('public.delivery-person-application-status', [
            'prefillReference' => (string) $request->query('ref', ''),
        ]);
    }

    public function lookupStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $application = DeliveryPersonApplication::query()
            ->where('reference', strtoupper(trim($validated['reference'])))
            ->where('email', mb_strtolower(trim($validated['email'])))
            ->first();

        if (! $application || ! Hash::check($validated['password'], $application->password)) {
            return response()->json([
                'success' => false,
                'message' => 'No matching application was found for those credentials.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'application' => $this->formatApplicationForApplicant($application),
        ]);
    }

    public function resubmitInfo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
            'additional_notes' => ['required', 'string', 'min:3', 'max:2000'],
            'identity_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'driving_licence_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'vehicle_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $application = DeliveryPersonApplication::query()
            ->where('reference', strtoupper(trim($validated['reference'])))
            ->where('email', mb_strtolower(trim($validated['email'])))
            ->first();

        if (! $application || ! Hash::check($validated['password'], $application->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to verify application credentials.',
            ], 403);
        }

        if ($application->status !== DeliveryPersonApplication::STATUS_ADDITIONAL_INFO_REQUIRED) {
            return response()->json([
                'success' => false,
                'message' => 'This application is not currently awaiting additional information.',
            ], 422);
        }

        DB::transaction(function () use ($request, $application, $validated) {
            $docFields = [
                'identity_document' => 'identity',
                'driving_licence_document' => 'driving_licence',
                'vehicle_document' => 'vehicle_insurance',
            ];

            foreach ($docFields as $input => $docType) {
                $uploadedFile = $request->file($input);
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $newDoc = $this->storePrivateDocument($application, $uploadedFile, $docType);
                    DeliveryPersonApplicationDocument::query()
                        ->where('application_id', $application->id)
                        ->where('document_type', $docType)
                        ->where('id', '!=', $newDoc->id)
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'replaced_by_id' => $newDoc->id,
                        ]);
                }
            }

            $application->update([
                'status' => DeliveryPersonApplication::STATUS_UNDER_REVIEW,
                'additional_info_requested' => null,
            ]);

            $application->recordEvent(
                'additional_info_submitted',
                'applicant',
                null,
                $validated['additional_notes']
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Your additional information has been submitted and your application is back under review.',
            'application' => $this->formatApplicationForApplicant($application->fresh()),
        ]);
    }

    private function normalizeInternationalPhone(string $dialCode, string $rawPhone, string $isoCode = 'GB'): ?array
    {
        $dialDigits = preg_replace('/\D+/', '', $dialCode);
        $localDigits = preg_replace('/\D+/', '', $rawPhone);
        if ($dialDigits === '' || $localDigits === '') {
            return null;
        }

        // Strip leading trunk zero if user entered national format (e.g. 07700900111 -> 7700900111)
        if (str_starts_with($localDigits, '0') && strlen($localDigits) >= 8) {
            $localDigits = ltrim($localDigits, '0');
        }

        $e164Digits = $dialDigits . $localDigits;
        if (strlen($e164Digits) < 8 || strlen($e164Digits) > 15) {
            return null;
        }

        $countryMinMax = [
            'GB' => [9, 10],
            'US' => [10, 10],
            'CA' => [10, 10],
            'BD' => [10, 10],
            'IN' => [10, 10],
            'PK' => [10, 10],
            'AU' => [9, 9],
            'AE' => [8, 9],
            'SA' => [9, 9],
        ];
        if (isset($countryMinMax[$isoCode])) {
            [$min, $max] = $countryMinMax[$isoCode];
            if (strlen($localDigits) < $min || strlen($localDigits) > $max) {
                return null;
            }
        }

        return [
            'e164' => '+' . $e164Digits,
            'digits' => $e164Digits,
        ];
    }

    private function storePrivateDocument(DeliveryPersonApplication $application, $file, string $docType): DeliveryPersonApplicationDocument
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $filename = Str::uuid()->toString() . '.' . $ext;
        $dir = 'private/delivery-person-applications/' . $application->reference;
        $path = $dir . '/' . $filename;

        $sourcePath = $file->getRealPath() ?: $file->getPathname();
        if (! is_string($sourcePath) || $sourcePath === '' || ! is_file($sourcePath)) {
            throw new \RuntimeException('Uploaded file could not be read from temporary storage.');
        }

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open uploaded file stream.');
        }

        try {
            Storage::disk('local')->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $application->allDocuments()->create([
            'document_type' => $docType,
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'disk' => 'local',
            'storage_path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'file_size' => (int) $file->getSize(),
            'is_active' => true,
            'uploaded_at' => now(),
        ]);
    }

    private function formatApplicationForApplicant(DeliveryPersonApplication $application): array
    {
        return [
            'reference' => $application->reference,
            'full_name' => $application->full_name,
            'status' => $application->status,
            'status_label' => DeliveryPersonApplication::statusLabel($application->status),
            'submitted_at' => optional($application->submitted_at)->format('d M Y, H:i'),
            'reviewed_at' => optional($application->reviewed_at)->format('d M Y, H:i'),
            'additional_info_requested' => $application->additional_info_requested,
            'applicant_message' => $application->applicant_message,
            'can_resubmit' => $application->status === DeliveryPersonApplication::STATUS_ADDITIONAL_INFO_REQUIRED,
            'can_login' => $application->status === DeliveryPersonApplication::STATUS_APPROVED,
            'login_url' => route('delivery-person.login'),
        ];
    }

    private function notifySafely(DeliveryPersonApplication $application, string $eventType): void
    {
        try {
            Notification::route('mail', $application->email)
                ->notify(new DeliveryPersonApplicationNotification($application, $eventType));
        } catch (\Throwable $e) {
            Log::warning('DeliveryPersonApplication notification failed', [
                'application_id' => $application->id,
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
