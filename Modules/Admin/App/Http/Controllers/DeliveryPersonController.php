<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Support\CountryDialCodes;
use App\Support\CurrencyCatalog;
use App\Support\InternationalAddressHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Services\DeliveryLogisticsService;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonDocument;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPostcode;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;

class DeliveryPersonController extends Controller
{
    public function __construct(
        protected DeliveryLogisticsService $logistics,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $query = DeliveryPerson::query()
            ->with(['store:id,name', 'postcodes'])
            ->withCount([
                'assignments as active_assignments_count' => fn ($q) => $q->whereIn('status', [
                    DeliveryAssignment::STATUS_PENDING,
                    DeliveryAssignment::STATUS_ASSIGNED,
                    DeliveryAssignment::STATUS_ACCEPTED,
                    DeliveryAssignment::STATUS_PICKED_UP,
                    DeliveryAssignment::STATUS_IN_TRANSIT,
                ]),
                'assignments as completed_orders_count' => fn ($q) => $q
                    ->whereIn('status', [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED])
                    ->select(DB::raw('COUNT(DISTINCT booking_id)')),
            ])
            ->latest('id');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = trim((string) $request->input('work_status', ''))) {
            $query->where('work_status', $status);
        }

        if ($assignState = trim((string) $request->input('assignment_state', ''))) {
            if ($assignState === 'assigned') {
                $query->whereHas('assignments', fn ($q) => $q->whereIn('status', [
                    DeliveryAssignment::STATUS_PENDING,
                    DeliveryAssignment::STATUS_ASSIGNED,
                    DeliveryAssignment::STATUS_ACCEPTED,
                    DeliveryAssignment::STATUS_PICKED_UP,
                    DeliveryAssignment::STATUS_IN_TRANSIT,
                ]));
            } elseif ($assignState === 'unassigned') {
                $query->whereDoesntHave('assignments', fn ($q) => $q->whereIn('status', [
                    DeliveryAssignment::STATUS_PENDING,
                    DeliveryAssignment::STATUS_ASSIGNED,
                    DeliveryAssignment::STATUS_ACCEPTED,
                    DeliveryAssignment::STATUS_PICKED_UP,
                    DeliveryAssignment::STATUS_IN_TRANSIT,
                ]));
            }
        }

        if ($postcode = trim((string) $request->input('postcode', ''))) {
            $compact = UkAddress::compactPostcode($postcode);
            $query->whereHas('postcodes', fn ($q) => $q->where('postcode_compact', 'like', "%{$compact}%"));
        }

        if ($storeId = $request->input('store_id')) {
            $query->where('store_id', $storeId);
        }

        if ($from = trim((string) $request->input('date_from', ''))) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = trim((string) $request->input('date_to', ''))) {
            $query->whereDate('created_at', '<=', $to);
        }

        $deliveryPersons = $query->paginate(15)->withQueryString();

        // Compute ledger outstanding balance per displayed person
        $balances = [];
        foreach ($deliveryPersons as $person) {
            $balances[$person->id] = $this->logistics->getBalanceSummary($person);
        }

        // Database-driven top dashboard statistics
        $totalCredits = (int) DeliveryPersonLedgerEntry::where('direction', 'credit')->sum('amount_minor');
        $totalDebits = (int) DeliveryPersonLedgerEntry::where('direction', 'debit')->sum('amount_minor');

        $stats = [
            'total' => DeliveryPerson::count(),
            'active' => DeliveryPerson::where('work_status', 'active')->count(),
            'inactive' => DeliveryPerson::whereIn('work_status', ['inactive', 'suspended'])->count(),
            'currently_assigned' => DeliveryPerson::whereHas('assignments', fn ($q) => $q->whereIn('status', [
                DeliveryAssignment::STATUS_PENDING,
                DeliveryAssignment::STATUS_ASSIGNED,
                DeliveryAssignment::STATUS_ACCEPTED,
                DeliveryAssignment::STATUS_PICKED_UP,
                DeliveryAssignment::STATUS_IN_TRANSIT,
            ]))->count(),
            'completed_assignments' => DeliveryAssignment::whereIn('status', [
                DeliveryAssignment::STATUS_HANDED_OVER,
                DeliveryAssignment::STATUS_COMPLETED,
            ])->count(),
            'outstanding_balance_minor' => max(0, $totalCredits - $totalDebits),
        ];

        $stores = Store::orderBy('name')->get(['id', 'name']);
        $coveredPostcodes = DeliveryPersonPostcode::query()
            ->select('postcode')
            ->distinct()
            ->orderBy('postcode')
            ->pluck('postcode');
        $currencyService = $this->currencyService;

        return view('admin::delivery-persons.index', compact(
            'deliveryPersons',
            'balances',
            'stats',
            'stores',
            'coveredPostcodes',
            'currencyService'
        ));
    }

    public function create()
    {
        $stores = Store::orderBy('name')->get(['id', 'name', 'store_code']);
        $countries = CountryDialCodes::all();
        $currencies = CurrencyCatalog::CURRENCIES;
        $globalSettings = $this->currencyService->getGlobalSettings();
        $deliveryPerson = null;
        $selectedPostcodes = old('coverage_postcodes', []);

        return view('admin::delivery-persons.form', compact(
            'stores',
            'countries',
            'currencies',
            'globalSettings',
            'deliveryPerson',
            'selectedPostcodes'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePersonRequest($request);

        $person = DB::transaction(function () use ($request, $validated) {
            $currency = strtoupper((string) ($validated['currency'] ?? $this->currencyService->code()));
            $collectionMinor = $this->currencyService->toMinor($validated['per_collection_rate'] ?? 0, $currency);
            $deliveryMinor = $this->currencyService->toMinor($validated['per_delivery_rate'] ?? 0, $currency);

            $countryMeta = InternationalAddressHelper::findCountry($validated['country_code']);
            $countryName = $countryMeta['name'] ?? $validated['country_code'];

            $residentialPostcode = strtoupper((string) preg_replace('/\s+/', ' ', trim($validated['residential_postcode'])));
            if (strtoupper($validated['country_code']) === 'GB') {
                $residentialPostcode = UkAddress::normalizePostcode($residentialPostcode) ?? $residentialPostcode;
            }

            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $profilePhotoPath = $request->file('profile_photo')->store('delivery-persons/photos', 'public');
            }

            $person = DeliveryPerson::create([
                'code' => 'DP-' . strtoupper(Str::random(6)),
                'full_name' => $validated['full_name'],
                'email' => strtolower(trim($validated['email'])),
                'phone_country_code' => $validated['phone_country_code'] ?? '+44',
                'phone' => $validated['phone'],
                'date_of_birth' => $validated['date_of_birth'],
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'profile_photo' => $profilePhotoPath,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'],
                'county_region' => $validated['county_region'] ?? null,
                'country_code' => strtoupper($validated['country_code']),
                'country_name' => $countryName,
                'residential_postcode' => $residentialPostcode,
                'full_residential_address' => InternationalAddressHelper::composeFullAddress(
                    $validated['address_line_1'],
                    $validated['address_line_2'] ?? null,
                    $validated['city'],
                    $validated['county_region'] ?? null,
                    $residentialPostcode,
                    $countryName
                ),
                'employment_type' => $validated['employment_type'],
                'work_status' => $validated['work_status'],
                'availability_status' => $validated['availability_status'],
                'joining_date' => $validated['joining_date'] ?? null,
                'store_id' => $validated['store_id'] ?? null,
                'vehicle_type' => $validated['vehicle_type'] ?? null,
                'vehicle_registration' => $validated['vehicle_registration'] ?? null,
                'license_details' => $validated['license_details'] ?? null,
                'work_notes' => $validated['work_notes'] ?? null,
                'payment_calculation_type' => $validated['payment_calculation_type'],
                'per_collection_rate_minor' => $collectionMinor,
                'per_delivery_rate_minor' => $deliveryMinor,
                'currency' => $currency,
                'payment_effective_date' => $validated['payment_effective_date'] ?? null,
                'payment_notes' => $validated['payment_notes'] ?? null,
                'created_by_admin_id' => Auth::guard('admin')->id(),
            ]);

            $person->code = 'DP-' . str_pad((string) $person->id, 4, '0', STR_PAD_LEFT);
            $person->save();

            // Sync multi-select UK coverage postcodes (validates against uk_addresses)
            $this->logistics->syncPostcodes($person, $validated['coverage_postcodes'] ?? []);

            // Store private identity document outside public storage
            $this->storeIdentityDocument($request, $person, $validated);

            return $person;
        });

        return redirect()
            ->route('admin.delivery-persons.show', $person)
            ->with('success', "Delivery person \"{$person->full_name}\" ({$person->code}) created successfully.");
    }

    public function show(DeliveryPerson $deliveryPerson)
    {
        $deliveryPerson->load([
            'store',
            'postcodes',
            'documents.verifiedByAdmin',
            'assignments.booking',
            'assignments.store',
            'events.booking',
            'events.actorAdmin',
            'ledgerEntries.booking',
            'ledgerEntries.assignment',
            'ledgerEntries.createdByAdmin',
            'payouts.recordedByAdmin',
        ]);

        $stats = $this->logistics->getPerformanceStats($deliveryPerson);
        $balance = $this->logistics->getBalanceSummary($deliveryPerson);
        $currencyService = $this->currencyService;

        $activeAssignments = $deliveryPerson->assignments->whereIn('status', [
            DeliveryAssignment::STATUS_PENDING,
            DeliveryAssignment::STATUS_ASSIGNED,
            DeliveryAssignment::STATUS_ACCEPTED,
            DeliveryAssignment::STATUS_PICKED_UP,
            DeliveryAssignment::STATUS_IN_TRANSIT,
        ]);

        $completedAssignments = $deliveryPerson->assignments->whereIn('status', [
            DeliveryAssignment::STATUS_HANDED_OVER,
            DeliveryAssignment::STATUS_COMPLETED,
        ]);

        return view('admin::delivery-persons.show', compact(
            'deliveryPerson',
            'stats',
            'balance',
            'currencyService',
            'activeAssignments',
            'completedAssignments'
        ));
    }

    public function edit(DeliveryPerson $deliveryPerson)
    {
        $deliveryPerson->load(['postcodes', 'documents']);
        $stores = Store::orderBy('name')->get(['id', 'name', 'store_code']);
        $countries = CountryDialCodes::all();
        $currencies = CurrencyCatalog::CURRENCIES;
        $globalSettings = $this->currencyService->getGlobalSettings();
        $selectedPostcodes = old('coverage_postcodes', $deliveryPerson->postcodes->pluck('postcode')->all());

        return view('admin::delivery-persons.form', compact(
            'deliveryPerson',
            'stores',
            'countries',
            'currencies',
            'globalSettings',
            'selectedPostcodes'
        ));
    }

    public function update(Request $request, DeliveryPerson $deliveryPerson): RedirectResponse
    {
        $validated = $this->validatePersonRequest($request, $deliveryPerson);

        DB::transaction(function () use ($request, $deliveryPerson, $validated) {
            $currency = strtoupper((string) ($validated['currency'] ?? $deliveryPerson->currency));
            $collectionMinor = $this->currencyService->toMinor($validated['per_collection_rate'] ?? 0, $currency);
            $deliveryMinor = $this->currencyService->toMinor($validated['per_delivery_rate'] ?? 0, $currency);

            $countryMeta = InternationalAddressHelper::findCountry($validated['country_code']);
            $countryName = $countryMeta['name'] ?? $validated['country_code'];

            $residentialPostcode = strtoupper((string) preg_replace('/\s+/', ' ', trim($validated['residential_postcode'])));
            if (strtoupper($validated['country_code']) === 'GB') {
                $residentialPostcode = UkAddress::normalizePostcode($residentialPostcode) ?? $residentialPostcode;
            }

            $photoPath = $deliveryPerson->profile_photo;
            if ($request->hasFile('profile_photo')) {
                if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $request->file('profile_photo')->store('delivery-persons/photos', 'public');
            }

            $deliveryPerson->update([
                'full_name' => $validated['full_name'],
                'email' => strtolower(trim($validated['email'])),
                'phone_country_code' => $validated['phone_country_code'] ?? $deliveryPerson->phone_country_code,
                'phone' => $validated['phone'],
                'date_of_birth' => $validated['date_of_birth'],
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'profile_photo' => $photoPath,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'],
                'county_region' => $validated['county_region'] ?? null,
                'country_code' => strtoupper($validated['country_code']),
                'country_name' => $countryName,
                'residential_postcode' => $residentialPostcode,
                'full_residential_address' => InternationalAddressHelper::composeFullAddress(
                    $validated['address_line_1'],
                    $validated['address_line_2'] ?? null,
                    $validated['city'],
                    $validated['county_region'] ?? null,
                    $residentialPostcode,
                    $countryName
                ),
                'employment_type' => $validated['employment_type'],
                'work_status' => $validated['work_status'],
                'availability_status' => $validated['availability_status'],
                'joining_date' => $validated['joining_date'] ?? null,
                'store_id' => $validated['store_id'] ?? null,
                'vehicle_type' => $validated['vehicle_type'] ?? null,
                'vehicle_registration' => $validated['vehicle_registration'] ?? null,
                'license_details' => $validated['license_details'] ?? null,
                'work_notes' => $validated['work_notes'] ?? null,
                'payment_calculation_type' => $validated['payment_calculation_type'],
                'per_collection_rate_minor' => $collectionMinor,
                'per_delivery_rate_minor' => $deliveryMinor,
                'currency' => $currency,
                'payment_effective_date' => $validated['payment_effective_date'] ?? null,
                'payment_notes' => $validated['payment_notes'] ?? null,
                'updated_by_admin_id' => Auth::guard('admin')->id(),
            ]);

            $this->logistics->syncPostcodes($deliveryPerson, $validated['coverage_postcodes'] ?? []);

            if ($request->filled('document_number') || $request->hasFile('document_front_image')) {
                $this->storeIdentityDocument($request, $deliveryPerson, $validated);
            }
        });

        return redirect()
            ->route('admin.delivery-persons.show', $deliveryPerson)
            ->with('success', "Delivery person \"{$deliveryPerson->full_name}\" updated successfully.");
    }

    public function toggleStatus(DeliveryPerson $deliveryPerson): RedirectResponse
    {
        $deliveryPerson->work_status = $deliveryPerson->work_status === 'active' ? 'inactive' : 'active';
        $deliveryPerson->updated_by_admin_id = Auth::guard('admin')->id();
        $deliveryPerson->save();

        $activeCount = $deliveryPerson->assignments()->whereIn('status', [
            DeliveryAssignment::STATUS_PENDING,
            DeliveryAssignment::STATUS_ASSIGNED,
            DeliveryAssignment::STATUS_ACCEPTED,
            DeliveryAssignment::STATUS_PICKED_UP,
            DeliveryAssignment::STATUS_IN_TRANSIT,
        ])->count();

        $msg = $deliveryPerson->work_status === 'active'
            ? "{$deliveryPerson->full_name} has been activated and can receive new assignments."
            : "{$deliveryPerson->full_name} has been deactivated." . ($activeCount > 0 ? " Note: {$activeCount} active assignment(s) remain open—please reassign if needed." : '');

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, DeliveryPerson $deliveryPerson): RedirectResponse
    {
        $hasProtectedHistory = $deliveryPerson->assignments()->exists()
            || $deliveryPerson->ledgerEntries()->exists()
            || $deliveryPerson->payouts()->exists();

        $reason = trim((string) $request->input('archive_reason', 'Archived by administrator'));

        if ($hasProtectedHistory) {
            $deliveryPerson->work_status = 'inactive';
            $deliveryPerson->archive_reason = $reason;
            $deliveryPerson->updated_by_admin_id = Auth::guard('admin')->id();
            $deliveryPerson->save();
            $deliveryPerson->delete();

            return redirect()
                ->route('admin.delivery-persons.index')
                ->with('success', "{$deliveryPerson->full_name} has been archived safely. Historical assignments and financial ledger records were preserved.");
        }

        // No protected history: clean deletion
        foreach ($deliveryPerson->documents as $doc) {
            if ($doc->front_image_path && Storage::disk('local')->exists($doc->front_image_path)) {
                Storage::disk('local')->delete($doc->front_image_path);
            }
            if ($doc->back_image_path && Storage::disk('local')->exists($doc->back_image_path)) {
                Storage::disk('local')->delete($doc->back_image_path);
            }
        }
        $deliveryPerson->forceDelete();

        return redirect()
            ->route('admin.delivery-persons.index')
            ->with('success', "{$deliveryPerson->full_name} deleted permanently.");
    }

    public function downloadDocument(DeliveryPerson $deliveryPerson, DeliveryPersonDocument $document, string $side = 'front')
    {
        if ($document->delivery_person_id !== $deliveryPerson->id) {
            abort(404);
        }

        $path = $side === 'back' ? $document->back_image_path : $document->front_image_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('local')->response($path);
    }

    public function verifyDocument(Request $request, DeliveryPerson $deliveryPerson, DeliveryPersonDocument $document): RedirectResponse
    {
        if ($document->delivery_person_id !== $deliveryPerson->id) {
            abort(404);
        }

        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(['pending', 'verified', 'rejected', 'expired'])],
            'verification_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $document->update([
            'verification_status' => $validated['verification_status'],
            'verification_notes' => $validated['verification_notes'] ?? null,
            'verified_by_admin_id' => Auth::guard('admin')->id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Identity document verification status updated.');
    }

    protected function validatePersonRequest(Request $request, ?DeliveryPerson $existing = null): array
    {
        $isCreate = $existing === null;
        $uniqueEmailRule = Rule::unique('delivery_persons', 'email');
        if ($existing) {
            $uniqueEmailRule->ignore($existing->id);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', $uniqueEmailRule],
            'phone_country_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30'],
            'date_of_birth' => ['required', 'date', 'before:today', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],

            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'county_region' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'max:4'],
            'residential_postcode' => ['required', 'string', 'max:20'],

            'coverage_postcodes' => ['required', 'array', 'min:1'],
            'coverage_postcodes.*' => ['required', 'string', 'max:20'],

            'employment_type' => ['required', Rule::in(['employee', 'contractor', 'other'])],
            'work_status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'availability_status' => ['required', Rule::in(['available', 'busy', 'off_duty'])],
            'joining_date' => ['nullable', 'date'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'vehicle_type' => ['nullable', 'string', 'max:40'],
            'vehicle_registration' => ['nullable', 'string', 'max:60'],
            'license_details' => ['nullable', 'string', 'max:150'],
            'work_notes' => ['nullable', 'string', 'max:2000'],

            'payment_calculation_type' => ['required', Rule::in(['fixed_per_assignment', 'separate_rates', 'admin_override'])],
            'per_collection_rate' => ['required', 'numeric', 'min:0'],
            'per_delivery_rate' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'payment_effective_date' => ['nullable', 'date'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],

            'document_type' => [$isCreate ? 'required' : 'nullable', Rule::in(['national_id', 'passport'])],
            'document_number' => [$isCreate ? 'required' : 'nullable', 'string', 'max:80'],
            'document_front_image' => [$isCreate ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'document_back_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'document_expiry_date' => ['nullable', 'date', 'after:today'],
            'verification_status' => ['nullable', Rule::in(['pending', 'verified', 'rejected', 'expired'])],
        ], [
            'date_of_birth.before_or_equal' => 'Delivery person must be at least 18 years old.',
            'coverage_postcodes.required' => 'Please select at least one UK delivery coverage postcode.',
        ]);

        $validated['phone'] = InternationalAddressHelper::validatePhone($validated['phone'], 'phone') ?? $validated['phone'];
        if (! empty($validated['emergency_contact_phone'])) {
            InternationalAddressHelper::validatePhone($validated['emergency_contact_phone'], 'emergency_contact_phone');
        }

        return $validated;
    }

    protected function storeIdentityDocument(Request $request, DeliveryPerson $person, array $validated): void
    {
        $doc = $person->documents()->latest('id')->first() ?? new DeliveryPersonDocument([
            'delivery_person_id' => $person->id,
        ]);

        if (! empty($validated['document_type'])) {
            $doc->document_type = $validated['document_type'];
        }

        if (! empty($validated['document_number'])) {
            $doc->setPlainDocumentNumber($validated['document_number']);
        }

        if ($request->hasFile('document_front_image')) {
            if ($doc->front_image_path && Storage::disk('local')->exists($doc->front_image_path)) {
                Storage::disk('local')->delete($doc->front_image_path);
            }
            $file = $request->file('document_front_image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $doc->front_image_path = $file->storeAs("delivery-persons/{$person->id}/documents", $filename, 'local');
        }

        if ($request->hasFile('document_back_image')) {
            if ($doc->back_image_path && Storage::disk('local')->exists($doc->back_image_path)) {
                Storage::disk('local')->delete($doc->back_image_path);
            }
            $file = $request->file('document_back_image');
            $filename = Str::uuid() . '_back.' . $file->getClientOriginalExtension();
            $doc->back_image_path = $file->storeAs("delivery-persons/{$person->id}/documents", $filename, 'local');
        }

        $doc->expiry_date = $validated['document_expiry_date'] ?? $doc->expiry_date;
        $doc->verification_status = $validated['verification_status'] ?? ($doc->verification_status ?: 'pending');
        if ($doc->verification_status === 'verified' && ! $doc->verified_at) {
            $doc->verified_by_admin_id = Auth::guard('admin')->id();
            $doc->verified_at = now();
        }
        $doc->save();
    }
}
