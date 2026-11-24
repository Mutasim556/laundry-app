<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CustomerPaymentMethod;
use App\Services\StripePaymentService;
use App\Support\CountryDialCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\BookingAvailabilitySetting;
use Modules\Admin\Models\BookingBlockedDate;
use Modules\Admin\Models\BookingDriverInstruction;
use Modules\Admin\Models\BookingTimeSlot;
use Modules\Admin\Models\Hotel;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\UkAddress;

class BookingAddressController extends Controller
{
    /**
     * Standard UK Postcode regex pattern.
     */
    public const UK_POSTCODE_REGEX = '/^([A-Z]{1,2}\d[A-Z\d]?|GIR)\s*(\d[A-Z]{2})$/i';

    /**
     * Evaluate which of the 5 booking steps are completed, available, or locked based on validated session data.
     */
    public static function evaluateBookingSteps(array $booking, int $currentStep): array
    {
        $step1Completed = ! empty($booking['services']) && is_array($booking['services']) && count($booking['services']) > 0;
        $step2Completed = $step1Completed && ! empty($booking['address']['selected_address']);
        $step3Completed = $step2Completed
            && ! empty($booking['schedule']['collection_date'])
            && ! empty($booking['schedule']['collection_slot'])
            && ! empty($booking['schedule']['delivery_date'])
            && ! empty($booking['schedule']['delivery_slot']);
        $step4Completed = $step3Completed
            && (! empty($booking['details']['first_name']) || ! empty($booking['details']['customer_name']))
            && (! empty($booking['details']['phone_number']) || ! empty($booking['details']['customer_phone']))
            && ! empty($booking['details']['customer_email']);
        $step5Completed = $step4Completed && ! empty($booking['confirmed_at']);
        $isLocked = $step5Completed || ! empty($booking['is_locked']);

        $definitions = [
            1 => [
                'number' => 1,
                'key' => 'services',
                'label' => 'Services',
                'short_label' => 'Services',
                'url' => route('booking.services'),
                'completed' => $step1Completed,
                'available' => ! $isLocked,
                'missing_requirement' => $isLocked ? 'This booking is confirmed and locked. Details can no longer be edited.' : '',
            ],
            2 => [
                'number' => 2,
                'key' => 'address',
                'label' => 'Address',
                'short_label' => 'Address',
                'url' => route('booking.address'),
                'completed' => $step2Completed,
                'available' => ! $isLocked && $step1Completed,
                'missing_requirement' => $isLocked
                    ? 'This booking is confirmed and locked. Details can no longer be edited.'
                    : 'Please complete Step 1 (Select Services) before proceeding to Address.',
            ],
            3 => [
                'number' => 3,
                'key' => 'schedule',
                'label' => 'Schedule',
                'short_label' => 'Schedule',
                'url' => route('booking.schedule'),
                'completed' => $step3Completed,
                'available' => ! $isLocked && $step2Completed,
                'missing_requirement' => $isLocked
                    ? 'This booking is confirmed and locked. Details can no longer be edited.'
                    : (! $step1Completed
                        ? 'Please complete Step 1 (Select Services) first.'
                        : 'Please complete Step 2 (Address) before choosing a Schedule.'),
            ],
            4 => [
                'number' => 4,
                'key' => 'details',
                'label' => 'Contact',
                'short_label' => 'Contact',
                'url' => route('booking.details'),
                'completed' => $step4Completed,
                'available' => ! $isLocked && $step3Completed,
                'missing_requirement' => $isLocked
                    ? 'This booking is confirmed and locked. Details can no longer be edited.'
                    : (! $step1Completed
                        ? 'Please complete Step 1 (Select Services) first.'
                        : (! $step2Completed
                            ? 'Please complete Step 2 (Address) first.'
                            : 'Please complete Step 3 (Schedule) before entering Contact Details.')),
            ],
            5 => [
                'number' => 5,
                'key' => 'confirm',
                'label' => 'Confirm',
                'short_label' => 'Confirm',
                'url' => ($isLocked && ! empty($booking['reference']))
                    ? route('booking.success', $booking['reference'])
                    : route('booking.confirm'),
                'completed' => $step5Completed,
                'available' => $step4Completed,
                'missing_requirement' => ! $step1Completed
                    ? 'Please complete Step 1 (Select Services) first.'
                    : (! $step2Completed
                        ? 'Please complete Step 2 (Address) first.'
                        : (! $step3Completed
                            ? 'Please complete Step 3 (Schedule) first.'
                            : 'Please complete Step 4 (Contact Details) before reviewing and confirming your booking.')),
            ],
        ];

        foreach ($definitions as $num => &$step) {
            $step['is_current'] = ($num === $currentStep);
            if ($step['is_current']) {
                $step['state'] = 'current';
            } elseif ($step['completed']) {
                $step['state'] = 'completed';
            } else {
                $step['state'] = 'upcoming';
            }
        }

        return $definitions;
    }

    protected function isSessionBookingLocked(array $booking): bool
    {
        if (! empty($booking['is_locked']) || ! empty($booking['confirmed_at'])) {
            return true;
        }

        if (! empty($booking['reference'])) {
            return Booking::where('reference', $booking['reference'])
                ->whereNotNull('confirmed_at')
                ->where('is_locked', true)
                ->exists();
        }

        return false;
    }

    protected function rejectLockedBookingMutation(Request $request, array $booking): JsonResponse|\Illuminate\Http\Response
    {
        $message = 'This booking is already confirmed and permanently locked. Confirmed bookings cannot be modified.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'reference' => $booking['reference'] ?? null,
            ], 409);
        }

        return response()->view('errors.409', [
            'message' => $message,
            'reference' => $booking['reference'] ?? null,
        ], 409);
    }

    /**
     * Check if a target step's prerequisites are satisfied; if not, return a redirect to the earliest incomplete step.
     */
    protected function enforceStepPrerequisites(array $booking, int $targetStep): ?RedirectResponse
    {
        if ($this->isSessionBookingLocked($booking) && ! empty($booking['reference'])) {
            return redirect()->route('booking.success', ['reference' => $booking['reference']]);
        }

        $steps = self::evaluateBookingSteps($booking, $targetStep);
        if (! ($steps[$targetStep]['available'] ?? false)) {
            // Find earliest incomplete step
            for ($i = 1; $i < $targetStep; $i++) {
                if (! $steps[$i]['completed']) {
                    return redirect($steps[$i]['url'])
                        ->with('booking_step_warning', $steps[$targetStep]['missing_requirement']);
                }
            }
        }

        return null;
    }

    /**
     * Display STEP 2 of the Laundry Booking System (Address / Hotel Selection).
     */
    public function showAddressStep(Request $request): View|RedirectResponse
    {
        $booking = $request->session()->get('booking', []);
        if ($this->isSessionBookingLocked($booking) && ! empty($booking['reference'])) {
            return redirect()->route('booking.success', ['reference' => $booking['reference']]);
        }

        // Merge incoming query params if user clicked Book Now from a specific category or service
        if ($request->filled('service_id')) {
            $service = Service::query()->with(['unit', 'category', 'item'])->where('is_active', true)->find($request->input('service_id'));
            if ($service) {
                $booking['service_id'] = $service->id;
                $booking['service_name'] = $service->name;
                $booking['price'] = (float) $service->price;
                $booking['unit'] = $service->unit?->short_name ?: ($service->unit?->name ?? 'item');
                $booking['quantity'] = max(1, (int) $request->input('quantity', $booking['quantity'] ?? 1));
                $booking['category_slug'] = $service->category?->slug ?? ($service->item?->category?->slug ?? ($booking['category_slug'] ?? 'wash-fold'));
                $booking['category_name'] = $service->category?->name ?? ($service->item?->category?->name ?? ($booking['category_name'] ?? 'Laundry Service'));
            }
        } elseif ($request->filled('category')) {
            $categorySlug = (string) $request->input('category');
            $cat = ItemCategory::query()->where('is_active', true)->where('slug', $categorySlug)->first();
            if ($cat) {
                $booking['category_id'] = $cat->id;
                $booking['category_slug'] = $cat->slug;
                $booking['category_name'] = $cat->name;
            } elseif ($categorySlug === 'other-services') {
                $booking['category_slug'] = 'other-services';
                $booking['category_name'] = 'Other Services';
            }
        }

        if ($request->filled('quantity')) {
            $booking['quantity'] = max(1, (int) $request->input('quantity'));
        }

        if ($request->filled('return_to') && $request->input('return_to') === 'confirm') {
            $request->session()->put('booking_return_to_confirm', true);
        } elseif (! $request->filled('return_to')) {
            // Preserve existing flag if already in an edit flow
        }

        $request->session()->put('booking', $booking);

        // Enforce that Step 1 (Select Services) is completed before accessing Step 2 via direct URL
        if (empty($booking['services']) && empty($booking['service_id']) && empty($booking['category_slug']) && ! $request->filled('step')) {
            if ($redirect = $this->enforceStepPrerequisites($booking, 2)) {
                return $redirect;
            }
        }

        $savedAddresses = $request->user()
            ? $request->user()->addresses()->get()
            : collect();
        $initialStep = $request->query('step'); // optional: 'choose', 'postcode', 'hotel', 'summary'

        return view('booking.address', [
            'booking' => $booking,
            'savedAddresses' => $savedAddresses,
            'initialStep' => $initialStep,
            'hasPremiseProvider' => $this->hasConfiguredPremiseProvider(),
            'currentBookingStep' => 2,
            'bookingSteps' => self::evaluateBookingSteps($booking, 2),
        ]);
    }

    /**
     * Format and normalize a UK postcode to standard UPPERCASE "OUTWARD INWARD" format.
     */
    public static function normalizeUkPostcode(string $raw): ?string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', trim($raw)));
        if (preg_match('/^([A-Z]{1,2}\d[A-Z\d]?|GIR)(\d[A-Z]{2})$/', $clean, $matches)) {
            return $matches[1] . ' ' . $matches[2];
        }

        return null;
    }

    /**
     * Check whether an external premise-level UK postcode API key is configured.
     */
    protected function hasConfiguredPremiseProvider(): bool
    {
        return ! empty(config('services.uk_address.ideal_postcodes_key'))
            || ! empty(config('services.uk_address.getaddress_key'));
    }

    /**
     * Public API Endpoint: Search active UK addresses by postcode.
     * GET /api/addresses/search?postcode=E10BJ
     */
    public function apiSearchAddresses(Request $request): JsonResponse
    {
        $rawPostcode = (string) $request->query('postcode', '');
        $normalized = self::normalizeUkPostcode($rawPostcode);

        if (! $normalized) {
            return response()->json([
                'success' => false,
                'postcode' => strtoupper(trim($rawPostcode)),
                'message' => 'Please enter a valid UK postcode (for example, E1 0BJ or SW1A 1AA).',
                'addresses' => [],
            ], 422);
        }

        $paginator = UkAddress::query()
            ->active()
            ->forPostcode($normalized)
            ->orderByRaw('LENGTH(building_number), building_number ASC')
            ->orderBy('address_line_1')
            ->paginate(50);

        $addresses = collect($paginator->items())->map(fn (UkAddress $addr) => [
            'id' => $addr->id,
            'full_address' => $addr->full_address,
            'postcode' => $addr->postcode,
            'city' => strtoupper((string) $addr->city),
            'is_demo' => (bool) $addr->is_demo,
        ])->values()->all();

        return response()->json([
            'success' => true,
            'postcode' => $normalized,
            'addresses' => $addresses,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * AJAX Endpoint: Validate UK Postcode and Lookup Matching Addresses.
     * Priority: 1) Local uk_addresses DB, 2) Configured external provider, 3) Postcodes.io / Nominatim, 4) Manual fallback.
     */
    public function lookupPostcode(Request $request): JsonResponse
    {
        $rawPostcode = (string) $request->query('postcode', '');
        $normalized = self::normalizeUkPostcode($rawPostcode);

        if (! $normalized) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Please enter a valid UK postcode (for example, E1 0BJ or SW1A 1AA).',
                'addresses' => [],
                'address_items' => [],
            ], 422);
        }

        // 1. Query local database (active addresses only)
        $localMatches = UkAddress::query()
            ->active()
            ->forPostcode($normalized)
            ->orderByRaw('LENGTH(building_number), building_number ASC')
            ->orderBy('address_line_1')
            ->limit(50)
            ->get();

        if ($localMatches->isNotEmpty()) {
            $addressItems = $localMatches->map(fn (UkAddress $addr) => [
                'id' => $addr->id,
                'full_address' => $addr->full_address,
            ])->values()->all();

            $addressStrings = $localMatches->map(fn (UkAddress $addr) => $addr->full_address)
                ->unique()
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'valid' => true,
                'postcode' => $normalized,
                'provider' => 'local_database',
                'premise_level' => true,
                'addresses' => $addressStrings,
                'address_items' => $addressItems,
                'notice' => 'Select your address from the list below or click "My address isn\'t listed" to enter it manually.',
            ]);
        }

        $provider = config('services.uk_address.provider', 'auto');
        $idealKey = config('services.uk_address.ideal_postcodes_key');
        $getAddressKey = config('services.uk_address.getaddress_key');
        $postcodesIoUrl = rtrim(config('services.uk_address.postcodes_io_url', 'https://api.postcodes.io'), '/');
        $nominatimUrl = rtrim(config('services.uk_address.nominatim_url', 'https://nominatim.openstreetmap.org'), '/');

        // 1. If Ideal Postcodes API key is configured
        if (($provider === 'ideal_postcodes' || $provider === 'auto') && ! empty($idealKey)) {
            try {
                $response = Http::timeout(6)->acceptJson()->get(
                    'https://api.ideal-postcodes.co.uk/v1/postcodes/' . rawurlencode($normalized),
                    ['api_key' => $idealKey]
                );

                if ($response->status() === 404) {
                    return response()->json([
                        'valid' => false,
                        'postcode' => $normalized,
                        'message' => 'We could not find this UK postcode. Please check and try again.',
                        'addresses' => [],
                    ], 404);
                }

                if ($response->successful()) {
                    $hits = $response->json('result', []);
                    $addresses = collect($hits)->map(function (array $item) use ($normalized) {
                        $parts = array_filter([
                            $item['line_1'] ?? '',
                            $item['line_2'] ?? '',
                            $item['line_3'] ?? '',
                            $item['post_town'] ?? '',
                            $item['postcode'] ?? $normalized,
                        ]);

                        return strtoupper(implode(', ', $parts));
                    })->unique()->values()->all();

                    return response()->json([
                        'valid' => true,
                        'postcode' => $normalized,
                        'provider' => 'ideal_postcodes',
                        'premise_level' => true,
                        'addresses' => $addresses,
                    ]);
                }
            } catch (\Throwable $e) {
                // Fall through to secondary lookup
            }
        }

        // 2. If getAddress.io API key is configured
        if (($provider === 'getaddress' || $provider === 'auto') && ! empty($getAddressKey)) {
            try {
                $response = Http::timeout(6)->acceptJson()->get(
                    'https://api.getAddress.io/find/' . rawurlencode($normalized),
                    ['api-key' => $getAddressKey, 'expand' => 'true']
                );

                if ($response->status() === 404) {
                    return response()->json([
                        'valid' => false,
                        'postcode' => $normalized,
                        'message' => 'We could not find this UK postcode. Please check and try again.',
                        'addresses' => [],
                    ], 404);
                }

                if ($response->successful()) {
                    $hits = $response->json('addresses', []);
                    $addresses = collect($hits)->map(function ($item) use ($normalized) {
                        if (is_array($item)) {
                            $parts = array_filter([
                                $item['line_1'] ?? '',
                                $item['line_2'] ?? '',
                                $item['line_3'] ?? '',
                                $item['town_or_city'] ?? '',
                                $normalized,
                            ]);

                            return strtoupper(implode(', ', $parts));
                        }

                        return strtoupper(trim((string) $item) . ', ' . $normalized);
                    })->unique()->values()->all();

                    return response()->json([
                        'valid' => true,
                        'postcode' => $normalized,
                        'provider' => 'getaddress',
                        'premise_level' => true,
                        'addresses' => $addresses,
                    ]);
                }
            } catch (\Throwable $e) {
                // Fall through to free Postcodes.io + OpenStreetMap verification
            }
        }

        // 3. Free Real UK Postcode Verification via Postcodes.io + OpenStreetMap Nominatim
        try {
            $pioResponse = Http::timeout(6)->acceptJson()->get(
                $postcodesIoUrl . '/postcodes/' . rawurlencode($normalized)
            );

            if ($pioResponse->status() === 404) {
                return response()->json([
                    'valid' => false,
                    'postcode' => $normalized,
                    'message' => 'This UK postcode does not exist. Please check your postcode or enter your address manually.',
                    'addresses' => [],
                ], 404);
            }

            if ($pioResponse->successful() && $pioResponse->json('status') === 200) {
                $result = $pioResponse->json('result', []);
                $lat = $result['latitude'] ?? null;
                $lon = $result['longitude'] ?? null;
                $ward = strtoupper((string) ($result['admin_ward'] ?? ''));
                $district = strtoupper((string) ($result['admin_district'] ?? $result['parliamentary_constituency'] ?? ''));
                $region = strtoupper((string) ($result['region'] ?? $result['country'] ?? 'LONDON'));

                $addresses = [];

                // Query OpenStreetMap Nominatim for real street/building data at this postcode
                try {
                    $nomSearch = Http::timeout(5)
                        ->withHeaders(['User-Agent' => 'FreshGoLaundryApp/1.0 (Local Laragon)'])
                        ->acceptJson()
                        ->get($nominatimUrl . '/search', [
                            'postalcode' => $normalized,
                            'countrycodes' => 'gb',
                            'format' => 'jsonv2',
                            'addressdetails' => 1,
                            'limit' => 20,
                        ]);

                    if ($nomSearch->successful()) {
                        foreach ($nomSearch->json() ?? [] as $place) {
                            $addr = $place['address'] ?? [];
                            $houseNumber = $addr['house_number'] ?? '';
                            $building = $addr['building'] ?? ($place['name'] ?? '');
                            $road = $addr['road'] ?? ($addr['pedestrian'] ?? ($addr['suburb'] ?? ''));
                            $city = $addr['city'] ?? ($addr['town'] ?? ($addr['borough'] ?? ($district ?: $region)));

                            $lineParts = array_filter([
                                $houseNumber && $road ? ($houseNumber . ' ' . $road) : ($building ?: $road),
                                $building && $road && $building !== $road ? $road : null,
                                $ward && $ward !== strtoupper((string) $city) ? $ward : null,
                                $city,
                                $normalized,
                            ]);

                            if (count($lineParts) >= 2) {
                                $addresses[] = strtoupper(implode(', ', array_unique($lineParts)));
                            }
                        }
                    }

                    // Also do reverse geocode on exact postcode centroid if lat/lon available
                    if ($lat && $lon) {
                        $nomRev = Http::timeout(5)
                            ->withHeaders(['User-Agent' => 'FreshGoLaundryApp/1.0 (Local Laragon)'])
                            ->acceptJson()
                            ->get($nominatimUrl . '/reverse', [
                                'lat' => $lat,
                                'lon' => $lon,
                                'format' => 'jsonv2',
                                'addressdetails' => 1,
                            ]);

                        if ($nomRev->successful()) {
                            $rAddr = $nomRev->json('address', []);
                            $road = $rAddr['road'] ?? ($rAddr['pedestrian'] ?? '');
                            $building = $rAddr['building'] ?? ($nomRev->json('name') ?? '');
                            $suburb = $rAddr['suburb'] ?? ($rAddr['neighbourhood'] ?? $ward);
                            $city = $rAddr['city'] ?? ($rAddr['town'] ?? ($district ?: 'LONDON'));

                            if ($road) {
                                if ($building && strtoupper($building) !== strtoupper($road)) {
                                    $addresses[] = strtoupper(implode(', ', array_filter([$building, $road, $city, $normalized])));
                                }
                                $addresses[] = strtoupper(implode(', ', array_filter([$road, $suburb, $city, $normalized])));
                            }
                        }
                    }
                } catch (\Throwable $nomErr) {
                    // Nominatim optional enrichment failed; fall back to Postcodes.io administrative location
                }

                // Always include verified Postcodes.io area descriptor if no premise-level hits were returned
                if (empty($addresses)) {
                    $areaLine = array_filter([$ward, $district, $region !== $district ? $region : null, $normalized]);
                    if (count($areaLine) >= 2) {
                        $addresses[] = strtoupper(implode(', ', array_unique($areaLine)));
                    }
                }

                $addresses = array_values(array_unique(array_filter($addresses)));

                return response()->json([
                    'valid' => true,
                    'postcode' => $normalized,
                    'provider' => 'postcodes_io',
                    'premise_level' => false,
                    'area_info' => [
                        'ward' => $ward,
                        'district' => $district,
                        'region' => $region,
                    ],
                    'addresses' => $addresses,
                    'notice' => 'Postcode verified via Postcodes.io & OpenStreetMap. Select a street match below or click "My address isn\'t listed" to enter your flat/house number.',
                ]);
            }
        } catch (\Throwable $e) {
            // Network/offline fallback: Syntax is valid, allow manual address entry smoothly
            return response()->json([
                'valid' => true,
                'postcode' => $normalized,
                'provider' => 'offline_fallback',
                'premise_level' => false,
                'addresses' => [],
                'lookup_unavailable' => true,
                'notice' => 'Live address lookup is currently unreachable. Please enter your address manually below.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'postcode' => $normalized,
            'provider' => 'manual_fallback',
            'premise_level' => false,
            'addresses' => [],
            'notice' => 'Please select an address or enter your full address manually.',
        ]);
    }

    /**
     * AJAX / API Endpoint: Search Active UK Hotels from Database by query or coordinates.
     * Supports GET /api/hotels/search?q=grand and GET /booking/hotel-search
     */
    public function searchHotels(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $lat = $request->query('lat');
        $lon = $request->query('lon');

        if ($query === '' && (! is_numeric($lat) || ! is_numeric($lon))) {
            return response()->json([
                'success' => true,
                'hotels' => [],
                'message' => 'Enter a hotel name or use "Show hotels near me".',
            ]);
        }

        // Nearby search using stored hotel coordinates (no fake results)
        if (is_numeric($lat) && is_numeric($lon)) {
            $userLat = (float) $lat;
            $userLon = (float) $lon;

            $nearbyQuery = Hotel::query()->active()->withCoordinates();
            if ($query !== '') {
                $nearbyQuery->search($query);
            }

            $nearbyHotels = $nearbyQuery->get()
                ->map(function (Hotel $h) use ($userLat, $userLon) {
                    $h->distance_km = Hotel::haversineDistanceKm($userLat, $userLon, (float) $h->latitude, (float) $h->longitude);

                    return $h;
                })
                ->sortBy('distance_km')
                ->take(15)
                ->values();

            if ($nearbyHotels->isEmpty()) {
                $hasAnyWithCoords = Hotel::query()->active()->withCoordinates()->exists();

                return response()->json([
                    'success' => true,
                    'provider' => 'local_database',
                    'coordinates_unavailable' => ! $hasAnyWithCoords,
                    'hotels' => [],
                    'message' => ! $hasAnyWithCoords
                        ? 'Nearby hotel coordinates are not available in our directory. Please search by hotel name or enter it manually.'
                        : 'No active hotels found near your location. You can enter your hotel manually below.',
                ]);
            }

            return response()->json([
                'success' => true,
                'provider' => 'local_database',
                'hotels' => $nearbyHotels->map(fn (Hotel $h) => [
                    'id' => $h->id,
                    'name' => strtoupper(trim($h->name)),
                    'address' => $h->formatted_address,
                    'postcode' => $h->postcode,
                    'city' => strtoupper(trim($h->city)),
                    'distance_km' => round((float) $h->distance_km, 2),
                ])->values()->all(),
            ]);
        }

        // Text search against active hotels
        $hotels = Hotel::query()
            ->active()
            ->search($query)
            ->orderBy('name')
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'provider' => 'local_database',
            'hotels' => $hotels->map(fn (Hotel $h) => [
                'id' => $h->id,
                'name' => strtoupper(trim($h->name)),
                'address' => $h->formatted_address,
                'postcode' => $h->postcode,
                'city' => strtoupper(trim($h->city)),
            ])->values()->all(),
            'message' => $hotels->isEmpty()
                ? 'No matching hotels found. Click "Can\'t find your hotel? Enter it manually" below.'
                : null,
        ]);
    }

    /**
     * Validate and store the selected collection address in the session.
     */
    public function storeAddress(Request $request): RedirectResponse|JsonResponse|\Illuminate\Http\Response
    {
        $booking = $request->session()->get('booking', []);
        if ($this->isSessionBookingLocked($booking)) {
            return $this->rejectLockedBookingMutation($request, $booking);
        }

        $collectionMode = $request->input('collection_mode', 'home_office');

        if ($collectionMode === 'hotel') {
            $validated = $request->validate([
                'collection_mode' => ['required', Rule::in(['home_office', 'hotel'])],
                'hotel_id' => ['nullable', 'integer'],
                'hotel_name' => ['required', 'string', 'min:2', 'max:180'],
                'hotel_address' => ['required', 'string', 'min:5', 'max:350'],
                'room_number' => ['required', 'string', 'min:1', 'max:50', 'regex:/^[A-Za-z0-9\-\s]+$/'],
                'hotel_collection_type' => ['required', Rule::in(['use_reception', 'meet_driver'])],
                'address_type' => ['nullable', Rule::in(['HOME', 'OFFICE', 'HOTEL'])],
                'address_details' => ['nullable', 'string', 'max:255'],
            ], [
                'hotel_name.required' => 'Please enter or select your hotel name.',
                'hotel_address.required' => 'Please provide the hotel street address or postcode.',
                'room_number.required' => 'Please enter your hotel room number.',
                'room_number.regex' => 'Room number may only contain letters, numbers, hyphens, and spaces.',
                'hotel_collection_type.required' => 'Please select a collection type (Use reception or Meet driver).',
                'hotel_collection_type.in' => 'Collection type must be Use reception or Meet driver.',
            ]);

            $hotelId = ! empty($validated['hotel_id']) ? (int) $validated['hotel_id'] : null;
            if ($hotelId !== null) {
                $activeHotel = Hotel::query()->where('id', $hotelId)->where('is_active', true)->first();
                if (! $activeHotel) {
                    $msg = 'The selected hotel is no longer active. Please select another hotel or enter your hotel manually.';
                    if ($request->expectsJson()) {
                        return response()->json(['message' => $msg, 'errors' => ['hotel_name' => [$msg]]], 422);
                    }

                    return back()->withErrors(['hotel_name' => $msg])->withInput();
                }
            }

            $addressData = [
                'collection_mode' => 'hotel',
                'hotel_id' => $hotelId,
                'hotel_name' => strtoupper(trim($validated['hotel_name'])),
                'selected_address' => strtoupper(trim($validated['hotel_address'])),
                'postcode' => self::extractPostcodeFromText($validated['hotel_address']),
                'address_type' => 'HOTEL',
                'room_number' => strtoupper(trim($validated['room_number'])),
                'hotel_collection_type' => $validated['hotel_collection_type'],
                'address_details' => strtoupper(trim((string) ($validated['address_details'] ?? ''))),
                'is_manual' => $hotelId === null,
                'updated_at' => now()->toDateTimeString(),
            ];
        } else {
            // Normalize postcode before validation
            $rawPostcode = (string) $request->input('postcode', '');
            $normalizedPostcode = self::normalizeUkPostcode($rawPostcode);

            if ($normalizedPostcode) {
                $request->merge(['postcode' => $normalizedPostcode]);
            }

            $addressTypeUpper = strtoupper((string) $request->input('address_type', 'HOME'));
            $isHotelType = ($addressTypeUpper === 'HOTEL');

            $validated = $request->validate([
                'collection_mode' => ['required', Rule::in(['home_office', 'hotel'])],
                'postcode' => ['required', 'string', 'regex:' . self::UK_POSTCODE_REGEX],
                'uk_address_id' => ['nullable', 'integer'],
                'selected_address' => ['nullable', 'string', 'max:350'],
                'manual_address' => ['nullable', 'string', 'max:350'],
                'is_manual' => ['nullable', 'boolean'],
                'address_type' => ['required', Rule::in(['HOME', 'OFFICE', 'HOTEL'])],
                'room_number' => [
                    $isHotelType ? 'required' : 'nullable',
                    'string',
                    'max:50',
                    'regex:/^[A-Za-z0-9\-\s]+$/',
                ],
                'hotel_collection_type' => [
                    'nullable',
                    Rule::in(['use_reception', 'meet_driver']),
                ],
                'address_details' => ['nullable', 'string', 'max:255'],
            ], [
                'postcode.required' => 'Please enter a UK postcode.',
                'postcode.regex' => 'Please enter a valid UK postcode (e.g. E1 0BJ).',
                'address_type.required' => 'Please choose an address type (HOME, OFFICE, or HOTEL).',
                'address_type.in' => 'Address type must be HOME, OFFICE, or HOTEL.',
                'room_number.required' => 'Please enter your hotel room number.',
                'room_number.regex' => 'Room number may only contain letters, numbers, hyphens, and spaces.',
            ]);

            $isManual = $request->boolean('is_manual');
            $finalAddress = $isManual
                ? strtoupper(trim((string) ($validated['manual_address'] ?? '')))
                : strtoupper(trim((string) ($validated['selected_address'] ?? '')));

            // Fallback if one of the two was filled
            if ($finalAddress === '') {
                $finalAddress = strtoupper(trim((string) ($validated['manual_address'] ?: $validated['selected_address'])));
            }

            if (mb_strlen($finalAddress) < 5) {
                $errorField = $isManual ? 'manual_address' : 'selected_address';
                $errorMsg = $isManual
                    ? 'Please enter your full street address.'
                    : 'Please select your address from the dropdown list or click "My address isn\'t listed".';

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $errorMsg,
                        'errors' => [$errorField => [$errorMsg]],
                    ], 422);
                }

                return back()->withErrors([$errorField => $errorMsg])->withInput();
            }

            // Append postcode to manual address if not already included
            if ($isManual && ! str_contains($finalAddress, $normalizedPostcode)) {
                $finalAddress = rtrim($finalAddress, ', ') . ', ' . $normalizedPostcode;
            }

            $ukAddressId = (! $isManual && ! empty($validated['uk_address_id']))
                ? (int) $validated['uk_address_id']
                : null;

            $addressData = [
                'collection_mode' => 'home_office',
                'uk_address_id' => $ukAddressId,
                'postcode' => $normalizedPostcode,
                'selected_address' => $finalAddress,
                'manual_address' => $isManual ? strtoupper(trim((string) ($validated['manual_address'] ?? ''))) : '',
                'is_manual' => $isManual,
                'address_type' => $addressTypeUpper,
                'room_number' => $isHotelType ? strtoupper(trim((string) ($validated['room_number'] ?? ''))) : null,
                'hotel_collection_type' => $isHotelType ? ($validated['hotel_collection_type'] ?? null) : null,
                'address_details' => strtoupper(trim((string) ($validated['address_details'] ?? ''))),
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        // Merge with existing booking session data without losing service/category/price
        $booking = $request->session()->get('booking', []);
        if ($request->filled('saved_customer_address_id') && $request->user()) {
            $savedId = (int) $request->input('saved_customer_address_id');
            $addressData['saved_customer_address_id'] = $savedId;
            $savedAddrModel = \App\Models\CustomerAddress::where('id', $savedId)
                ->where('user_id', $request->user()->id)
                ->first();
            if ($savedAddrModel && $savedAddrModel->hasContactDetails()) {
                $booking['address_contact'] = [
                    'customer_type' => $savedAddrModel->contact_type ?: 'individual',
                    'first_name' => $savedAddrModel->contact_first_name ?? '',
                    'last_name' => $savedAddrModel->contact_last_name ?? '',
                    'company_name' => $savedAddrModel->contact_company_name ?? '',
                    'tax_number' => $savedAddrModel->contact_tax_number ?? '',
                    'country_iso' => $savedAddrModel->contact_country_iso ?? '',
                    'country_code' => $savedAddrModel->contact_dialing_code ?: '+44',
                    'phone_number' => $savedAddrModel->contact_phone ?? '',
                    'customer_email' => $savedAddrModel->contact_email ?? '',
                ];
            } else {
                unset($booking['address_contact']);
            }
        }
        $booking['address'] = $addressData;

        // Synchronize hotel_collection_type with Step 3 Schedule driver instructions to avoid contradictory collection methods
        if (! empty($addressData['hotel_collection_type'])) {
            $mappedColInstr = $addressData['hotel_collection_type'] === 'use_reception'
                ? 'Collect from reception'
                : 'Collect from me in person';
            $mappedDelInstr = $addressData['hotel_collection_type'] === 'use_reception'
                ? 'Deliver to reception'
                : 'Deliver to me in person';

            $booking['schedule'] = array_merge($booking['schedule'] ?? [], [
                'collection_instructions' => $mappedColInstr,
                'delivery_instructions' => $mappedDelInstr,
            ]);
        }

        if ($request->filled('return_to') && $request->input('return_to') === 'confirm') {
            $request->session()->put('booking_return_to_confirm', true);
        } elseif (! $request->filled('return_to')) {
            // Preserve existing flag if already in an edit flow
        }

        $request->session()->put('booking', $booking);

        // Save to recent session addresses list (avoiding duplicates)
        $saved = collect($request->session()->get('saved_addresses', []))
            ->reject(fn ($item) => ($item['selected_address'] ?? '') === $addressData['selected_address'])
            ->prepend($addressData)
            ->take(5)
            ->values()
            ->all();
        $request->session()->put('saved_addresses', $saved);

        $stepsAfterSave = self::evaluateBookingSteps($booking, 2);
        $shouldReturnToConfirm = ($request->session()->pull('booking_return_to_confirm', false) || $request->input('return_to') === 'confirm')
            && ($stepsAfterSave[5]['available'] ?? false);

        $nextUrl = $shouldReturnToConfirm
            ? route('booking.confirm')
            : (! empty($booking['services'])
                ? route('booking.schedule')
                : route('booking.address', ['step' => 'summary']));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection address saved.',
                'booking' => $booking,
                'redirect' => $nextUrl,
            ]);
        }

        return redirect($nextUrl)
            ->with('booking_address_saved', true);
    }

    /**
     * Extract a UK postcode from a freeform address string if present.
     */
    protected static function extractPostcodeFromText(string $text): string
    {
        if (preg_match('/([A-Z]{1,2}\d[A-Z\d]?|GIR)\s*(\d[A-Z]{2})/i', $text, $matches)) {
            return strtoupper($matches[1] . ' ' . $matches[2]);
        }

        return '';
    }

    /**
     * Default descriptions and badges for laundry service categories matching the design screenshot.
     */
    public static function defaultServiceMeta(): array
    {
        return [
            'wash-fold' => [
                'subtitle' => 'Everyday laundry washed, dried and neatly folded.',
                'badge' => null,
                'icon' => '01_washing_machine.png',
                'is_wash_fold' => true,
            ],
            'wash-iron' => [
                'subtitle' => 'For everyday laundry that requires ironing.',
                'badge' => null,
                'icon' => '12_clean_shirt.png',
                'is_wash_fold' => false,
            ],
            'dry-cleaning' => [
                'subtitle' => 'For delicate items and fabrics.',
                'badge' => null,
                'icon' => '06_shirt_hanger.png',
                'is_wash_fold' => false,
            ],
            'ironing-only' => [
                'subtitle' => 'For items that are already clean.',
                'badge' => null,
                'icon' => '05_iron.png',
                'is_wash_fold' => false,
            ],
            'back-wash-iron' => [
                'subtitle' => 'Specialized deep wash and crisp pressing.',
                'badge' => null,
                'icon' => '08_wash_tub.png',
                'is_wash_fold' => false,
            ],
            'bulky-item-duvet' => [
                'subtitle' => 'For larger items that require extra care.',
                'badge' => '3 DAY SERVICE',
                'icon' => '07_folded_laundry.png',
                'is_wash_fold' => false,
            ],
            'repairs' => [
                'subtitle' => 'Expert alterations, stitching, and garment repairs.',
                'badge' => null,
                'icon' => '13_repairs.svg',
                'is_wash_fold' => false,
            ],
            'others' => [
                'subtitle' => 'Additional custom laundry and fabric care services.',
                'badge' => null,
                'icon' => '03_laundry_basket.png',
                'is_wash_fold' => false,
            ],
        ];
    }

    /**
     * Load available booking services (from database ItemCategory + Service relationships, or fallback defaults in unseeded test DB).
     */
    protected function getBookingServiceCatalog(): \Illuminate\Support\Collection
    {
        $metaMap = self::defaultServiceMeta();
        $categories = collect();

        try {
            $categories = ItemCategory::query()
                ->with(['services' => fn ($q) => $q->with(['unit', 'item'])->where('is_active', true)->orderBy('id')])
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            $categories = collect();
        }

        if ($categories->isEmpty()) {
            // Fallback only when database tables are empty (e.g. unseeded unit test memory DB)
            $fallbackList = [
                [
                    'id' => 1,
                    'name' => 'Wash & Fold',
                    'slug' => 'wash-fold',
                    'wash_options' => [
                        (object) [
                            'id' => 2,
                            'item_category_id' => 1,
                            'name' => 'Mixed Wash & Fold',
                            'slug' => 'mixed-wash-fold',
                            'price' => 600.00,
                            'unit_name' => 'kg',
                            'description' => 'All colours washed together.',
                        ],
                        (object) [
                            'id' => 1,
                            'item_category_id' => 1,
                            'name' => 'Separate Wash & Fold',
                            'slug' => 'separate-wash-fold',
                            'price' => 500.00,
                            'unit_name' => 'kg',
                            'description' => 'Light and dark clothes washed separately.',
                        ],
                    ],
                ],
                ['id' => 2, 'name' => 'Wash & Iron', 'slug' => 'wash-iron'],
                ['id' => 3, 'name' => 'Dry Cleaning', 'slug' => 'dry-cleaning'],
                ['id' => 4, 'name' => 'Ironing Only', 'slug' => 'ironing-only'],
                ['id' => 6, 'name' => 'Duvets & Bulky Items', 'slug' => 'bulky-item-duvet'],
                ['id' => 7, 'name' => 'Repairs', 'slug' => 'repairs'],
            ];

            return collect($fallbackList)->map(function (array $item) use ($metaMap) {
                $meta = $metaMap[$item['slug']] ?? $metaMap['others'];
                $washOptions = collect($item['wash_options'] ?? []);

                return (object) [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'original_name' => $item['name'],
                    'slug' => $item['slug'],
                    'subtitle' => $meta['subtitle'],
                    'badge' => $meta['badge'],
                    'icon_url' => asset('images/laundry-icons/' . $meta['icon']),
                    'is_wash_fold' => $meta['is_wash_fold'],
                    'wash_options' => $washOptions,
                    'items' => $washOptions,
                ];
            });
        }

        return $categories->map(function ($cat) use ($metaMap) {
            $slug = (string) $cat->slug;
            $meta = $metaMap[$slug] ?? $metaMap['others'];
            $isWashFold = $slug === 'wash-fold' || stripos($cat->name, 'wash & fold') !== false || stripos($cat->name, 'wash and fold') !== false;

            $displayName = $slug === 'bulky-item-duvet' ? 'Duvets & Bulky Items' : $cat->name;
            $subtitle = ! empty($cat->short_description) ? $cat->short_description : $meta['subtitle'];
            $catServices = $cat->services ?? collect();

            // For Wash & Fold, retrieve the actual database Service options (Mixed Wash & Fold, Separate Wash & Fold)
            $washOptions = collect();
            if ($isWashFold) {
                $washOptions = $catServices
                    ->sortBy(function ($svc) {
                        // Display Mixed Wash & Fold first, then Separate Wash & Fold as in requirements
                        if (stripos($svc->name, 'mixed') !== false || stripos($svc->slug, 'mixed') !== false) {
                            return 1;
                        }
                        if (stripos($svc->name, 'separate') !== false || stripos($svc->slug, 'separate') !== false) {
                            return 2;
                        }

                        return 3;
                    })
                    ->values()
                    ->map(function ($svc) {
                        $defaultDesc = stripos($svc->name, 'mixed') !== false
                            ? 'All colours washed together.'
                            : (stripos($svc->name, 'separate') !== false ? 'Light and dark clothes washed separately.' : '');

                        return (object) [
                            'id' => (int) $svc->id,
                            'item_category_id' => (int) $svc->item_category_id,
                            'name' => $svc->name,
                            'slug' => $svc->slug,
                            'price' => (float) $svc->price,
                            'unit_name' => $svc->unit?->short_name ?: ($svc->unit?->name ?? 'kg'),
                            'description' => ! empty($svc->description) ? $svc->description : $defaultDesc,
                        ];
                    });
            }

            return (object) [
                'id' => (int) $cat->id,
                'name' => $displayName,
                'original_name' => $cat->name,
                'slug' => $slug,
                'subtitle' => $subtitle,
                'badge' => $meta['badge'],
                'icon_url' => $cat->image_url ?: asset('images/laundry-icons/' . $meta['icon']),
                'is_wash_fold' => $isWashFold,
                'wash_options' => $washOptions,
                'items' => $catServices,
            ];
        });
    }

    /**
     * STEP 1: Display the Multi-Service Selection Page with database-driven Wash & Fold options
     * and independent "Add Special Request" per service.
     */
    public function showServicesStep(Request $request): View|RedirectResponse
    {
        if ($request->boolean('new')) {
            $request->session()->forget('booking');
        }

        $booking = $request->session()->get('booking', []);
        if ($this->isSessionBookingLocked($booking) && ! empty($booking['reference'])) {
            return redirect()->route('booking.success', ['reference' => $booking['reference']]);
        }

        $serviceCatalog = $this->getBookingServiceCatalog();

        // Map currently saved services from session by category/service card ID
        $savedServices = collect($booking['services'] ?? [])->keyBy(fn ($item) => (int) ($item['category_id'] ?? $item['service_id'] ?? 0));

        // If user clicked Book Now from a specific service_id (e.g. Mixed Wash & Fold ID 2, or Shirts Wash & Iron ID 3)
        if ($request->filled('service_id')) {
            $incomingServiceId = (int) $request->input('service_id');
            $dbService = null;
            try {
                $dbService = Service::query()->with(['unit', 'category', 'item'])->where('is_active', true)->find($incomingServiceId);
            } catch (\Throwable $e) {
                $dbService = null;
            }

            if ($dbService) {
                $catId = (int) ($dbService->item_category_id ?: ($dbService->item?->item_category_id ?? 0));
                $matchedCard = $serviceCatalog->firstWhere('id', $catId);

                if ($matchedCard) {
                    $existingEntry = $savedServices->get($matchedCard->id, []);
                    $savedServices->put($matchedCard->id, [
                        'category_id' => $matchedCard->id,
                        'service_id' => $matchedCard->is_wash_fold ? (int) $dbService->id : $matchedCard->id,
                        'option_service_id' => $matchedCard->is_wash_fold ? (int) $dbService->id : (int) $dbService->id,
                        'option_name' => $dbService->name,
                        'price' => (float) $dbService->price,
                        'unit' => $dbService->unit?->short_name ?: ($dbService->unit?->name ?? ''),
                        'name' => $matchedCard->name,
                        'slug' => $matchedCard->slug,
                        'special_request' => $existingEntry['special_request'] ?? '',
                    ]);

                    // Also store context in booking session
                    $booking['service_id'] = (int) $dbService->id;
                    $booking['service_name'] = $dbService->name;
                    $booking['price'] = (float) $dbService->price;
                    $booking['unit'] = $dbService->unit?->short_name ?: ($dbService->unit?->name ?? 'item');
                    $booking['category_id'] = $matchedCard->id;
                    $booking['category_slug'] = $matchedCard->slug;
                    $booking['category_name'] = $matchedCard->name;
                }
            }
        } elseif ($request->filled('category')) {
            $incomingSlug = (string) $request->input('category');
            $matchedCard = $serviceCatalog->firstWhere('slug', $incomingSlug);
            if ($matchedCard) {
                $booking['category_id'] = $matchedCard->id;
                $booking['category_slug'] = $matchedCard->slug;
                $booking['category_name'] = $matchedCard->name;

                if (! $savedServices->has($matchedCard->id)) {
                    $firstWashOpt = $matchedCard->is_wash_fold ? $matchedCard->wash_options->first() : null;
                    $savedServices->put($matchedCard->id, [
                        'category_id' => $matchedCard->id,
                        'service_id' => $firstWashOpt ? (int) $firstWashOpt->id : $matchedCard->id,
                        'option_service_id' => $firstWashOpt ? (int) $firstWashOpt->id : null,
                        'option_name' => $firstWashOpt ? $firstWashOpt->name : null,
                        'price' => $firstWashOpt ? (float) $firstWashOpt->price : null,
                        'unit' => $firstWashOpt ? $firstWashOpt->unit_name : null,
                        'name' => $matchedCard->name,
                        'slug' => $matchedCard->slug,
                        'special_request' => '',
                    ]);
                }
            }
        }

        if ($request->filled('return_to') && $request->input('return_to') === 'confirm') {
            $request->session()->put('booking_return_to_confirm', true);
        } elseif (! $request->filled('return_to')) {
            // Preserve existing flag if already in an edit flow
        }

        $request->session()->put('booking', $booking);

        return view('booking.services', [
            'booking' => $booking,
            'serviceCatalog' => $serviceCatalog,
            'savedServices' => $savedServices,
            'currentBookingStep' => 1,
            'bookingSteps' => self::evaluateBookingSteps($booking, 1),
        ]);
    }

    /**
     * Validate and store STEP 1 (Multi-Service Selection, database Wash & Fold option ID & price,
     * and independent special_request per service), then redirect to STEP 2 (Address Selection).
     */
    public function storeServicesStep(Request $request): RedirectResponse|JsonResponse|\Illuminate\Http\Response
    {
        $booking = $request->session()->get('booking', []);
        if ($this->isSessionBookingLocked($booking)) {
            return $this->rejectLockedBookingMutation($request, $booking);
        }

        $serviceCatalog = $this->getBookingServiceCatalog()->keyBy('id');
        $validCatalogIds = $serviceCatalog->keys()->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'selected_services' => ['required', 'array', 'min:1'],
            'selected_services.*' => ['required', 'integer', Rule::in($validCatalogIds)],
            'services_data' => ['nullable', 'array'],
            'services_data.*.wash_option_id' => ['nullable', 'integer'],
            'services_data.*.special_request' => ['nullable', 'string', 'max:500'],
        ], [
            'selected_services.required' => 'Please select at least one laundry service to continue.',
            'selected_services.min' => 'Please select at least one laundry service to continue.',
            'selected_services.*.in' => 'One or more selected services are invalid.',
            'services_data.*.special_request.max' => 'Special request instructions cannot exceed 500 characters.',
        ]);

        // Deduplicate selected service card IDs
        $selectedIds = collect($validated['selected_services'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $servicesDataInput = $request->input('services_data', []);
        $normalizedServices = [];

        foreach ($selectedIds as $cardId) {
            $catalogItem = $serviceCatalog->get($cardId);
            if (! $catalogItem) {
                continue;
            }

            $rowInput = $servicesDataInput[$cardId] ?? [];
            $isWashFold = (bool) $catalogItem->is_wash_fold;
            $specialRequest = isset($rowInput['special_request'])
                ? trim((string) $rowInput['special_request'])
                : '';

            if ($isWashFold) {
                $washOptionId = isset($rowInput['wash_option_id']) && $rowInput['wash_option_id'] !== ''
                    ? (int) $rowInput['wash_option_id']
                    : null;

                // Verify that the selected wash_option_id actually exists in Wash & Fold's database options
                $matchedOption = $washOptionId
                    ? $catalogItem->wash_options->firstWhere('id', $washOptionId)
                    : null;

                if (! $matchedOption) {
                    $errorMsg = 'Please select either Mixed Wash & Fold or Separate Wash & Fold.';
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => $errorMsg,
                            'errors' => ["services_data.{$cardId}.wash_option_id" => [$errorMsg]],
                        ], 422);
                    }

                    return back()
                        ->withErrors(["services_data.{$cardId}.wash_option_id" => $errorMsg])
                        ->withInput();
                }

                // Store actual database Service ID, price, description, and special request from server/DB (never user-supplied price)
                $normalizedServices[] = [
                    'category_id' => (int) $catalogItem->id,
                    'service_id' => (int) $matchedOption->id,
                    'option_service_id' => (int) $matchedOption->id,
                    'name' => $catalogItem->name,
                    'option_name' => $matchedOption->name,
                    'option_description' => $matchedOption->description,
                    'slug' => $catalogItem->slug,
                    'option_slug' => $matchedOption->slug,
                    'price' => (float) $matchedOption->price,
                    'unit' => $matchedOption->unit_name,
                    'special_request' => $specialRequest,
                ];
            } else {
                // Other services must NOT have a Wash & Fold option attached
                $firstCategoryService = $catalogItem->items->first();
                $normalizedServices[] = [
                    'category_id' => (int) $catalogItem->id,
                    'service_id' => (int) $catalogItem->id,
                    'option_service_id' => null,
                    'name' => $catalogItem->name,
                    'option_name' => null,
                    'option_description' => $catalogItem->subtitle,
                    'slug' => $catalogItem->slug,
                    'price' => $firstCategoryService ? (float) $firstCategoryService->price : null,
                    'unit' => $firstCategoryService?->unit?->short_name ?: ($firstCategoryService?->unit?->name ?? null),
                    'special_request' => $specialRequest,
                ];
            }
        }

        // Preserve existing booking session data (such as previously entered address) and save selected services
        $booking = $request->session()->get('booking', []);
        $booking['services'] = $normalizedServices;
        $booking['selected_service_ids'] = $selectedIds->all();
        $booking['updated_at'] = now()->toDateTimeString();
        if ($request->filled('return_to') && $request->input('return_to') === 'confirm') {
            $request->session()->put('booking_return_to_confirm', true);
        } elseif (! $request->filled('return_to')) {
            // Preserve existing flag if already in an edit flow
        }

        $request->session()->put('booking', $booking);

        $stepsAfterSave = self::evaluateBookingSteps($booking, 1);
        $shouldReturnToConfirm = ($request->session()->pull('booking_return_to_confirm', false) || $request->input('return_to') === 'confirm')
            && ($stepsAfterSave[5]['available'] ?? false);

        $nextRoute = $shouldReturnToConfirm ? route('booking.confirm') : route('booking.address');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Selected services saved. Proceeding to collection address.',
                'services' => $normalizedServices,
                'booking' => $booking,
                'redirect' => $nextRoute,
            ]);
        }

        return redirect($nextRoute)
            ->with('booking_services_saved', true);
    }

    /**
     * Retrieve active time slot labels from database (with fallback if table has no records).
     */
    public static function getAvailableTimeSlots(?string $type = null, ?string $dateYmd = null): array
    {
        if (BookingTimeSlot::query()->exists()) {
            $slotModels = self::getAvailableSlotModels($type ?: 'collection', $dateYmd ?: now()->format('Y-m-d'));

            return $slotModels->pluck('label')->values()->all();
        }

        return [
            '08:00 - 11:00',
            '11:00 - 14:00',
            '14:00 - 17:00',
            '17:00 - 20:00',
            '20:00 - 23:00',
        ];
    }

    /**
     * Retrieve active BookingTimeSlot models for a given type and date.
     */
    public static function getAvailableSlotModels(string $type, string $dateYmd): \Illuminate\Support\Collection
    {
        $tz = (string) BookingAvailabilitySetting::getValue('timezone', config('app.timezone', 'Europe/London'));
        try {
            $targetDate = \Illuminate\Support\Carbon::parse($dateYmd, $tz)->startOfDay();
        } catch (\Throwable $e) {
            return collect();
        }

        $weekday = (int) $targetDate->dayOfWeek;

        return BookingTimeSlot::query()
            ->active()
            ->ofType($type)
            ->forWeekday($weekday)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Retrieve active driver instruction models filtered by type and address type.
     */
    public static function getDriverInstructionModels(string $type, ?string $addressType = null): \Illuminate\Support\Collection
    {
        return BookingDriverInstruction::query()
            ->active()
            ->forType($type)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (BookingDriverInstruction $instr) => $addressType === null || $instr->supportsAddressType($addressType))
            ->values();
    }

    /**
     * Collection driver instruction options (database-driven with default fallback).
     */
    public static function getCollectionInstructionOptions(?string $addressType = null): array
    {
        if (BookingDriverInstruction::query()->exists()) {
            $models = self::getDriverInstructionModels('collection', $addressType);
            if ($models->isNotEmpty()) {
                return $models->pluck('title')->values()->all();
            }
        }

        return [
            'Collect from me in person',
            'Collect from reception',
            'Leave at a designated collection point',
        ];
    }

    /**
     * Delivery driver instruction options (database-driven with default fallback).
     */
    public static function getDeliveryInstructionOptions(?string $addressType = null): array
    {
        if (BookingDriverInstruction::query()->exists()) {
            $models = self::getDriverInstructionModels('delivery', $addressType);
            if ($models->isNotEmpty()) {
                return $models->pluck('title')->values()->all();
            }
        }

        return [
            'Deliver to me in person',
            'Deliver to reception',
            'Leave at an approved delivery location',
        ];
    }

    /**
     * Calculate minimum turnaround days based on selected services and admin setting.
     */
    public static function getMinimumTurnaroundDays(array $booking): int
    {
        $adminMin = max(1, (int) BookingAvailabilitySetting::getValue('min_delivery_turnaround_days', 1));
        $services = $booking['services'] ?? [];
        foreach ($services as $svc) {
            $slug = strtolower((string) ($svc['slug'] ?? ''));
            $name = strtolower((string) ($svc['name'] ?? ''));
            if ($slug === 'bulky-item-duvet' || str_contains($name, 'duvet') || str_contains($name, 'bulky')) {
                return max($adminMin, 3);
            }
        }

        return $adminMin;
    }

    /**
     * Generate eligible upcoming date options respecting weekdays, blocked dates, same-day rule, and turnaround.
     */
    public static function generateDateOptions(
        ?int $daysCount = null,
        string $type = 'collection',
        ?string $collectionDate = null,
        int $minTurnaroundDays = 1
    ): array {
        $tz = (string) BookingAvailabilitySetting::getValue('timezone', config('app.timezone', 'Europe/London'));
        try {
            $today = now($tz)->startOfDay();
        } catch (\Throwable $e) {
            $today = now()->startOfDay();
        }

        $maxAdvance = $daysCount ?? max(1, (int) BookingAvailabilitySetting::getValue('max_advance_days', 14));
        $sameDayAllowed = filter_var(BookingAvailabilitySetting::getValue('same_day_collection', '1'), FILTER_VALIDATE_BOOLEAN);
        $allowedWeekdays = BookingAvailabilitySetting::getWeekdays(
            $type === 'delivery' ? 'delivery_weekdays' : 'collection_weekdays'
        );

        $blockedDates = BookingBlockedDate::query()
            ->forType($type)
            ->whereDate('date', '>=', $today->format('Y-m-d'))
            ->pluck('date')
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('Y-m-d'))
            ->all();

        $hasSlotsInDb = BookingTimeSlot::query()->ofType($type)->exists();
        $slotWeekdays = $hasSlotsInDb
            ? BookingTimeSlot::query()->active()->ofType($type)->with('weekdays')->get()
                ->flatMap(fn (BookingTimeSlot $s) => $s->weekdays->pluck('weekday'))
                ->unique()
                ->all()
            : [0, 1, 2, 3, 4, 5, 6];

        $minDate = $today->copy();
        if ($type === 'collection' && ! $sameDayAllowed) {
            $minDate = $today->copy()->addDay();
        } elseif ($type === 'delivery' && $collectionDate) {
            try {
                $minDate = \Illuminate\Support\Carbon::parse($collectionDate, $tz)->startOfDay()->addDays($minTurnaroundDays);
            } catch (\Throwable $e) {
                $minDate = $today->copy()->addDays($minTurnaroundDays);
            }
        }

        $options = [];
        for ($i = 0; $i < $maxAdvance + ($type === 'delivery' ? $minTurnaroundDays + 7 : 0); $i++) {
            $date = $today->copy()->addDays($i);
            if ($date->lt($minDate)) {
                continue;
            }
            if (count($options) >= $maxAdvance) {
                break;
            }

            $ymd = $date->format('Y-m-d');
            $weekday = (int) $date->dayOfWeek;

            if (! in_array($weekday, $allowedWeekdays, true)) {
                continue;
            }
            if (! in_array($weekday, $slotWeekdays, true)) {
                continue;
            }
            if (in_array($ymd, $blockedDates, true)) {
                continue;
            }

            if ($ymd === $today->format('Y-m-d')) {
                $label = 'Today';
            } elseif ($ymd === $today->copy()->addDay()->format('Y-m-d')) {
                $label = 'Tomorrow';
            } else {
                $label = $date->format('D, j M');
            }

            $options[] = [
                'value' => $ymd,
                'label' => $label,
                'full_label' => $i <= 1 ? ($label . ' (' . $date->format('D, j M') . ')') : $date->format('D, j M Y'),
                'day_offset' => $i,
                'weekday' => $weekday,
            ];
        }

        return $options;
    }

    /**
     * AJAX Endpoint: GET /booking/available-dates?type=collection|delivery&collection_date=YYYY-MM-DD
     */
    public function getAvailableDates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['collection', 'delivery'])],
            'collection_date' => ['nullable', 'date'],
        ]);

        $booking = $request->session()->get('booking', []);
        $minTurnaroundDays = self::getMinimumTurnaroundDays($booking);
        $dates = self::generateDateOptions(
            null,
            $validated['type'],
            $validated['collection_date'] ?? null,
            $minTurnaroundDays
        );

        return response()->json([
            'success' => true,
            'type' => $validated['type'],
            'min_turnaround_days' => $minTurnaroundDays,
            'dates' => $dates,
        ]);
    }

    /**
     * AJAX Endpoint: GET /booking/available-slots?type=collection|delivery&date=YYYY-MM-DD
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['collection', 'delivery'])],
            'date' => ['required', 'date'],
        ]);

        $type = $validated['type'];
        $dateYmd = $validated['date'];

        $isBlocked = BookingBlockedDate::query()
            ->forType($type)
            ->whereDate('date', $dateYmd)
            ->exists();

        if ($isBlocked) {
            return response()->json([
                'success' => true,
                'type' => $type,
                'date' => $dateYmd,
                'slots' => [],
                'message' => 'This date is unavailable due to a scheduled holiday or closure.',
            ]);
        }

        if (BookingTimeSlot::query()->ofType($type)->exists()) {
            $slots = self::getAvailableSlotModels($type, $dateYmd)
                ->map(fn (BookingTimeSlot $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'label' => $s->label,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                ])
                ->values()
                ->all();
        } else {
            $slots = collect(self::getAvailableTimeSlots())->map(fn ($label) => [
                'id' => null,
                'label' => $label,
            ])->all();
        }

        return response()->json([
            'success' => true,
            'type' => $type,
            'date' => $dateYmd,
            'slots' => $slots,
            'message' => empty($slots) ? 'No active time slots are available for the selected date.' : null,
        ]);
    }

    /**
     * AJAX Endpoint: GET /booking/driver-instructions?type=collection|delivery&address_type=HOME|OFFICE|HOTEL
     */
    public function getDriverInstructions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['collection', 'delivery'])],
            'address_type' => ['nullable', Rule::in(['HOME', 'OFFICE', 'HOTEL'])],
        ]);

        $type = $validated['type'];
        $addressType = $validated['address_type'] ?? null;

        if (BookingDriverInstruction::query()->exists()) {
            $instructions = self::getDriverInstructionModels($type, $addressType)
                ->map(fn (BookingDriverInstruction $i) => [
                    'id' => $i->id,
                    'title' => $i->title,
                    'description' => $i->description,
                ])
                ->values()
                ->all();
        } else {
            $fallback = $type === 'delivery'
                ? self::getDeliveryInstructionOptions()
                : self::getCollectionInstructionOptions();
            $instructions = collect($fallback)->map(fn ($t) => ['id' => null, 'title' => $t])->all();
        }

        return response()->json([
            'success' => true,
            'type' => $type,
            'instructions' => $instructions,
        ]);
    }

    /**
     * STEP 3: Display Schedule Collection & Delivery Page ("Select time slots").
     * Server-side guarded: requires Step 1 (Services) and Step 2 (Address) to be completed.
     */
    public function showScheduleStep(Request $request): View|RedirectResponse
    {
        $booking = $request->session()->get('booking', []);
        if ($redirect = $this->enforceStepPrerequisites($booking, 3)) {
            return $redirect;
        }

        if ($request->filled('return_to') && $request->input('return_to') === 'confirm') {
            $request->session()->put('booking_return_to_confirm', true);
        }

        $addressType = strtoupper((string) ($booking['address']['address_type'] ?? 'HOME'));
        $minTurnaroundDays = self::getMinimumTurnaroundDays($booking);
        $collectionDateOptions = self::generateDateOptions(null, 'collection', null, $minTurnaroundDays);

        $savedSchedule = $booking['schedule'] ?? [];
        $selectedColDate = $savedSchedule['collection_date'] ?? ($collectionDateOptions[0]['value'] ?? now()->format('Y-m-d'));

        $deliveryDateOptions = self::generateDateOptions(null, 'delivery', $selectedColDate, $minTurnaroundDays);
        $selectedDelDate = $savedSchedule['delivery_date'] ?? ($deliveryDateOptions[0]['value'] ?? now()->addDays($minTurnaroundDays)->format('Y-m-d'));

        $collectionTimeSlots = self::getAvailableTimeSlots('collection', $selectedColDate);
        $deliveryTimeSlots = self::getAvailableTimeSlots('delivery', $selectedDelDate);
        $collectionInstructions = self::getCollectionInstructionOptions($addressType);
        $deliveryInstructions = self::getDeliveryInstructionOptions($addressType);

        $disabledNotices = [];
        if (! empty($savedSchedule['collection_slot']) && ! in_array($savedSchedule['collection_slot'], $collectionTimeSlots, true)) {
            $disabledNotices[] = "Your previously selected collection time slot ({$savedSchedule['collection_slot']}) is no longer available. Please select another slot.";
        }
        if (! empty($savedSchedule['delivery_slot']) && ! in_array($savedSchedule['delivery_slot'], $deliveryTimeSlots, true)) {
            $disabledNotices[] = "Your previously selected delivery time slot ({$savedSchedule['delivery_slot']}) is no longer available. Please select another slot.";
        }
        if (! empty($savedSchedule['collection_instructions']) && ! in_array($savedSchedule['collection_instructions'], $collectionInstructions, true)) {
            $disabledNotices[] = "Your previously selected collection instruction ({$savedSchedule['collection_instructions']}) is no longer available. Please choose another option.";
        }
        if (! empty($savedSchedule['delivery_instructions']) && ! in_array($savedSchedule['delivery_instructions'], $deliveryInstructions, true)) {
            $disabledNotices[] = "Your previously selected delivery instruction ({$savedSchedule['delivery_instructions']}) is no longer available. Please choose another option.";
        }

        return view('booking.schedule', [
            'booking' => $booking,
            'timeSlots' => $collectionTimeSlots,
            'collectionTimeSlots' => $collectionTimeSlots,
            'deliveryTimeSlots' => $deliveryTimeSlots,
            'collectionInstructions' => $collectionInstructions,
            'deliveryInstructions' => $deliveryInstructions,
            'minTurnaroundDays' => $minTurnaroundDays,
            'dateOptions' => $collectionDateOptions,
            'collectionDateOptions' => $collectionDateOptions,
            'deliveryDateOptions' => $deliveryDateOptions,
            'addressType' => $addressType,
            'disabledNotices' => $disabledNotices,
            'currentBookingStep' => 3,
            'bookingSteps' => self::evaluateBookingSteps($booking, 3),
        ]);
    }

    /**
     * Validate and store STEP 3 (Schedule Collection & Delivery), then redirect to STEP 4 (Contact Details).
     */
    public function storeScheduleStep(Request $request): RedirectResponse|JsonResponse|\Illuminate\Http\Response
    {
        $booking = $request->session()->get('booking', []);
        if ($this->isSessionBookingLocked($booking)) {
            return $this->rejectLockedBookingMutation($request, $booking);
        }
        if ($redirect = $this->enforceStepPrerequisites($booking, 3)) {
            return $redirect;
        }

        $addressType = strtoupper((string) ($booking['address']['address_type'] ?? 'HOME'));
        $minTurnaroundDays = self::getMinimumTurnaroundDays($booking);
        $todayStr = now()->startOfDay()->format('Y-m-d');
        $repeatEnabled = $request->boolean('repeat_order');

        $colDateInput = (string) $request->input('collection_date', '');
        $delDateInput = (string) $request->input('delivery_date', '');

        $collectionSlots = self::getAvailableTimeSlots('collection', $colDateInput ?: $todayStr);
        $deliverySlots = self::getAvailableTimeSlots('delivery', $delDateInput ?: $todayStr);
        $collectionInstructions = self::getCollectionInstructionOptions($addressType);
        $deliveryInstructions = self::getDeliveryInstructionOptions($addressType);

        $validated = $request->validate([
            'collection_date' => ['required', 'date', 'after_or_equal:' . $todayStr],
            'collection_slot' => ['required', 'string', 'max:100', Rule::in($collectionSlots)],
            'collection_instructions' => ['nullable', 'string', Rule::in($collectionInstructions)],
            'delivery_date' => ['required', 'date', 'after_or_equal:collection_date'],
            'delivery_slot' => ['required', 'string', 'max:100', Rule::in($deliverySlots)],
            'delivery_instructions' => ['nullable', 'string', Rule::in($deliveryInstructions)],
            'driver_notes' => ['nullable', 'string', 'max:500'],
            'repeat_order' => ['nullable', 'boolean'],
            'repeat_frequency' => [
                $repeatEnabled ? 'required' : 'nullable',
                Rule::in(['weekly', 'bi_weekly', 'monthly']),
            ],
        ], [
            'collection_date.required' => 'Please select a collection day.',
            'collection_date.after_or_equal' => 'Collection date cannot be in the past.',
            'collection_slot.required' => 'Please select a collection time slot.',
            'collection_slot.in' => 'The selected collection time slot is unavailable for that day.',
            'delivery_date.required' => 'Please select a delivery day.',
            'delivery_date.after_or_equal' => 'Delivery date cannot be earlier than the collection date.',
            'delivery_slot.required' => 'Please select a delivery time slot.',
            'delivery_slot.in' => 'The selected delivery time slot is unavailable for that day.',
            'driver_notes.max' => 'Driver note cannot exceed 500 characters.',
            'repeat_frequency.required' => 'Please select a repeat frequency (Weekly, Bi-weekly, or Monthly).',
            'repeat_frequency.in' => 'Repeat frequency must be Weekly, Bi-weekly, or Monthly.',
        ]);

        // Verify neither date is blocked
        $colBlocked = BookingBlockedDate::query()->forType('collection')->whereDate('date', $validated['collection_date'])->exists();
        if ($colBlocked) {
            return back()->withErrors(['collection_date' => 'Selected collection date is blocked/unavailable.'])->withInput();
        }

        $delBlocked = BookingBlockedDate::query()->forType('delivery')->whereDate('date', $validated['delivery_date'])->exists();
        if ($delBlocked) {
            return back()->withErrors(['delivery_date' => 'Selected delivery date is blocked/unavailable.'])->withInput();
        }

        // Enforce service-specific minimum turnaround time
        $colCarbon = \Illuminate\Support\Carbon::parse($validated['collection_date'])->startOfDay();
        $delCarbon = \Illuminate\Support\Carbon::parse($validated['delivery_date'])->startOfDay();
        $minDeliveryCarbon = $colCarbon->copy()->addDays($minTurnaroundDays);

        if ($delCarbon->lt($minDeliveryCarbon)) {
            $errMsg = $minTurnaroundDays >= 3
                ? 'Selected bulky/duvet services require at least a 3-day turnaround. Earliest delivery is ' . $minDeliveryCarbon->format('D, j M') . '.'
                : 'Delivery must be at least ' . $minTurnaroundDays . ' ' . ($minTurnaroundDays === 1 ? 'day' : 'days') . ' after collection (' . $minDeliveryCarbon->format('D, j M') . ' or later).';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $errMsg,
                    'errors' => ['delivery_date' => [$errMsg]],
                ], 422);
            }

            return back()->withErrors(['delivery_date' => $errMsg])->withInput();
        }

        $colSlotModel = BookingTimeSlot::query()->active()->ofType('collection')->where('label', trim($validated['collection_slot']))->first();
        $delSlotModel = BookingTimeSlot::query()->active()->ofType('delivery')->where('label', trim($validated['delivery_slot']))->first();
        $chosenColInstrTitle = $validated['collection_instructions'] ?? ($collectionInstructions[0] ?? 'Collect from me in person');
        $chosenDelInstrTitle = $validated['delivery_instructions'] ?? ($deliveryInstructions[0] ?? 'Deliver to me in person');
        $colInstrModel = BookingDriverInstruction::query()->active()->forType('collection')->where('title', $chosenColInstrTitle)->first();
        $delInstrModel = BookingDriverInstruction::query()->active()->forType('delivery')->where('title', $chosenDelInstrTitle)->first();

        $dateOptionsKeyed = collect(self::generateDateOptions(21, 'collection', null, $minTurnaroundDays))->keyBy('value');
        $colLabel = $dateOptionsKeyed->get($colCarbon->format('Y-m-d'))['label'] ?? $colCarbon->format('D, j M');
        $delLabel = $dateOptionsKeyed->get($delCarbon->format('Y-m-d'))['label'] ?? $delCarbon->format('D, j M');

        $booking['schedule'] = [
            'collection_date' => $colCarbon->format('Y-m-d'),
            'collection_day_label' => $colLabel,
            'collection_slot_id' => $colSlotModel?->id,
            'collection_slot' => trim($validated['collection_slot']),
            'collection_instruction_id' => $colInstrModel?->id,
            'collection_instructions' => $chosenColInstrTitle,
            'delivery_date' => $delCarbon->format('Y-m-d'),
            'delivery_day_label' => $delLabel,
            'delivery_slot_id' => $delSlotModel?->id,
            'delivery_slot' => trim($validated['delivery_slot']),
            'delivery_instruction_id' => $delInstrModel?->id,
            'delivery_instructions' => $chosenDelInstrTitle,
            'driver_notes' => trim((string) ($validated['driver_notes'] ?? '')),
            'repeat_order' => $repeatEnabled,
            'repeat_frequency' => $repeatEnabled ? ($validated['repeat_frequency'] ?? null) : null,
            'min_turnaround_days' => $minTurnaroundDays,
            'updated_at' => now()->toDateTimeString(),
        ];

        // Keep hotel_collection_type in booking.address synchronized with collection_instructions
        if (($booking['address']['address_type'] ?? '') === 'HOTEL' || ($booking['address']['collection_mode'] ?? '') === 'hotel') {
            $chosenColInstr = $booking['schedule']['collection_instructions'];
            if ($chosenColInstr === 'Collect from reception') {
                $booking['address']['hotel_collection_type'] = 'use_reception';
            } elseif ($chosenColInstr === 'Collect from me in person') {
                $booking['address']['hotel_collection_type'] = 'meet_driver';
            }
        }

        if ($request->filled('return_to') && $request->input('return_to') === 'confirm') {
            $request->session()->put('booking_return_to_confirm', true);
        }

        $request->session()->put('booking', $booking);

        $stepsAfterSave = self::evaluateBookingSteps($booking, 3);
        $shouldReturnToConfirm = ($request->session()->pull('booking_return_to_confirm', false) || $request->input('return_to') === 'confirm')
            && ($stepsAfterSave[5]['available'] ?? false);

        $nextRoute = $shouldReturnToConfirm ? route('booking.confirm') : route('booking.details');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'booking' => $booking,
                'redirect' => $nextRoute,
            ]);
        }

        return redirect($nextRoute)->with('booking_schedule_saved', true);
    }

    /**
     * STEP 4: Display Contact Details Page ("Enter contact details").
     * Server-side guarded: requires Steps 1, 2, and 3 to be completed.
     */
    public function showDetailsStep(Request $request): View|RedirectResponse
    {
        $booking = $request->session()->get('booking', []);
        if ($redirect = $this->enforceStepPrerequisites($booking, 4)) {
            return $redirect;
        }

        if ($request->filled('return_to') && $request->input('return_to') === 'confirm') {
            $request->session()->put('booking_return_to_confirm', true);
        }

        $countryList = CountryDialCodes::all();
        $countryCodes = collect($countryList)->mapWithKeys(fn ($c) => [$c['dial'] => $c['label']])->all();

        // Priority 1: Already entered/edited booking contact details
        // Priority 2: Contact details associated with the selected saved address
        // Priority 3: Authenticated customer profile
        // Priority 4: Blank fields
        if (empty($booking['details'])) {
            $addrContact = $booking['address_contact'] ?? null;
            if (! $addrContact && ! empty($booking['address']['saved_customer_address_id']) && $request->user()) {
                $savedAddrModel = \App\Models\CustomerAddress::where('id', (int) $booking['address']['saved_customer_address_id'])
                    ->where('user_id', $request->user()->id)
                    ->first();
                if ($savedAddrModel && $savedAddrModel->hasContactDetails()) {
                    $addrContact = [
                        'customer_type' => $savedAddrModel->contact_type ?: 'individual',
                        'first_name' => $savedAddrModel->contact_first_name ?? '',
                        'last_name' => $savedAddrModel->contact_last_name ?? '',
                        'company_name' => $savedAddrModel->contact_company_name ?? '',
                        'tax_number' => $savedAddrModel->contact_tax_number ?? '',
                        'country_iso' => $savedAddrModel->contact_country_iso ?? '',
                        'country_code' => $savedAddrModel->contact_dialing_code ?: '+44',
                        'phone_number' => $savedAddrModel->contact_phone ?? '',
                        'customer_email' => $savedAddrModel->contact_email ?? '',
                    ];
                }
            }

            if ($addrContact) {
                $booking['details'] = $addrContact;
            } elseif ($authUser = $request->user()) {
                $parts = preg_split('/\s+/', trim((string) $authUser->name), 2);
                $booking['details'] = [
                    'customer_type' => 'individual',
                    'first_name' => $parts[0] ?? '',
                    'last_name' => $parts[1] ?? '',
                    'country_code' => $authUser->country_code ?: '+44',
                    'phone_number' => $authUser->phone ?? '',
                    'customer_email' => $authUser->email ?? '',
                ];
            }
        }

        return view('booking.details', [
            'booking' => $booking,
            'countryCodes' => $countryCodes,
            'countryList' => $countryList,
            'currentBookingStep' => 4,
            'bookingSteps' => self::evaluateBookingSteps($booking, 4),
        ]);
    }

    /**
     * Validate and store STEP 4 (Customer Contact Details), then redirect to STEP 5 (Review & Confirm).
     */
    public function storeDetailsStep(Request $request): RedirectResponse|JsonResponse|\Illuminate\Http\Response
    {
        $booking = $request->session()->get('booking', []);
        if ($this->isSessionBookingLocked($booking)) {
            return $this->rejectLockedBookingMutation($request, $booking);
        }
        if ($redirect = $this->enforceStepPrerequisites($booking, 4)) {
            return $redirect;
        }

        // Backward compatibility if a legacy test posts customer_name + customer_phone directly
        if (! $request->filled('first_name') && $request->filled('customer_name')) {
            $parts = preg_split('/\s+/', trim((string) $request->input('customer_name')), 2);
            $request->merge([
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? ($parts[0] ?? 'Customer'),
            ]);
        }
        if (! $request->filled('phone_number') && $request->filled('customer_phone')) {
            $rawPhone = trim((string) $request->input('customer_phone'));
            if (preg_match('/^(\+\d{1,4})\s*(.+)$/', $rawPhone, $m)) {
                $request->merge([
                    'country_code' => $request->input('country_code', $m[1]),
                    'phone_number' => $m[2],
                ]);
            } else {
                $request->merge([
                    'country_code' => $request->input('country_code', '+44'),
                    'phone_number' => $rawPhone,
                ]);
            }
        }
        if (! $request->filled('customer_email') && $request->filled('email')) {
            $request->merge(['customer_email' => $request->input('email')]);
        }

        $customerType = strtolower((string) $request->input('customer_type', 'individual'));
        if (! in_array($customerType, ['individual', 'company'], true)) {
            $customerType = 'individual';
        }
        $request->merge(['customer_type' => $customerType]);

        $validated = $request->validate([
            'customer_type' => ['required', Rule::in(['individual', 'company'])],
            'first_name' => ['required', 'string', 'min:1', 'max:80'],
            'last_name' => ['required', 'string', 'min:1', 'max:80'],
            'company_name' => [
                $customerType === 'company' ? 'required' : 'nullable',
                'string',
                'max:150',
            ],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'country_code' => ['required', 'string', 'regex:/^\+\d{1,4}$/'],
            'country_iso' => ['nullable', 'string', 'max:4'],
            'phone_number' => ['required', 'string', 'min:6', 'max:25', 'regex:/^[0-9\s\-\(\)]+$/'],
            'customer_email' => ['required', 'email', 'max:150'],
            'contact_preference' => ['nullable', Rule::in(['phone', 'whatsapp', 'email'])],
        ], [
            'customer_type.required' => 'Please select Individual or Company.',
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'company_name.required' => 'Company name is required for company bookings.',
            'country_code.required' => 'Please select a country dialing code.',
            'phone_number.required' => 'Please enter your mobile number.',
            'phone_number.regex' => 'Please enter a valid phone number (digits only).',
            'customer_email.required' => 'Please enter your email address.',
            'customer_email.email' => 'Please enter a valid email address.',
        ]);

        $firstName = trim($validated['first_name']);
        $lastName = trim($validated['last_name']);
        $fullName = trim($firstName . ' ' . $lastName);
        $countryCode = trim($validated['country_code']);
        $phoneNumber = trim($validated['phone_number']);
        $fullPhone = trim($countryCode . ' ' . $phoneNumber);

        $booking['details'] = [
            'customer_type' => $validated['customer_type'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'customer_name' => $fullName,
            'company_name' => $validated['customer_type'] === 'company' ? trim((string) ($validated['company_name'] ?? '')) : '',
            'tax_number' => $validated['customer_type'] === 'company' ? trim((string) ($validated['tax_number'] ?? '')) : '',
            'country_code' => $countryCode,
            'country_iso' => strtoupper(trim((string) ($validated['country_iso'] ?? ''))),
            'phone_number' => $phoneNumber,
            'customer_phone' => $fullPhone,
            'customer_email' => trim($validated['customer_email']),
            'contact_preference' => $validated['contact_preference'] ?? 'phone',
            'updated_at' => now()->toDateTimeString(),
        ];

        $request->session()->pull('booking_return_to_confirm', false);
        $request->session()->put('booking', $booking);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'booking' => $booking,
                'redirect' => route('booking.confirm'),
            ]);
        }

        return redirect()->route('booking.confirm')->with('booking_details_saved', true);
    }

    /**
     * Configured BDT tip options matching the application's BDT currency.
     */
    public static function getDriverTipOptions(): array
    {
        $code = currencyCode();

        return [
            ['key' => '0', 'label' => 'No Tip', 'amount' => 0.0],
            ['key' => '50', 'label' => "{$code} 50", 'amount' => 50.0],
            ['key' => '100', 'label' => "{$code} 100", 'amount' => 100.0],
            ['key' => '150', 'label' => "{$code} 150", 'amount' => 150.0],
            ['key' => 'custom', 'label' => 'Custom Tip', 'amount' => null],
        ];
    }

    /**
     * Configured payment methods supported by the laundry business and compatible with active global currency.
     */
    public static function getSupportedPaymentMethods(): array
    {
        $currencyService = app(\App\Services\CurrencyService::class);
        $activeCurrency = $currencyService->code();

        if (\Illuminate\Support\Facades\Schema::hasTable('payment_gateways')
            && \Modules\Admin\App\Models\PaymentGateway::allowed()->exists()) {
            $gateways = \Modules\Admin\App\Models\PaymentGateway::activeOrdered()->get();
            $methods = [];

            foreach ($gateways as $gateway) {
                if (! $gateway->isUsable()) {
                    continue;
                }

                if ($gateway->gateway_code === 'stripe' && ! $currencyService->isStripeSupported($activeCurrency)) {
                    continue;
                }
                if ($gateway->gateway_code === 'paypal' && ! $currencyService->isPayPalSupported($activeCurrency)) {
                    continue;
                }

                $badge = match ($gateway->gateway_code) {
                    'stripe' => 'Stripe Secure',
                    'paypal' => 'PayPal',
                    'cod' => 'Pay on Delivery',
                    default => 'Active',
                };

                $methods[$gateway->gateway_code] = [
                    'code' => $gateway->gateway_code,
                    'name' => $gateway->display_name,
                    'badge' => $badge,
                    'description' => (string) ($gateway->description ?? ''),
                    'requires_online_gateway' => in_array($gateway->gateway_code, ['stripe', 'paypal'], true),
                    'is_configured' => $gateway->isConfigured(),
                    'environment' => $gateway->environment,
                    'sort_order' => $gateway->sort_order,
                    'enable_saved_cards' => (bool) $gateway->getPublicValue('enable_saved_cards', true),
                    'instructions' => (string) $gateway->getPublicValue('instructions', ''),
                ];
            }

            return $methods;
        }

        // Fallback only when payment_gateways table has not been seeded yet
        $stripeConfigured = ! empty(config('services.stripe.key')) && ! empty(config('services.stripe.secret'));
        $fallback = [];
        if ($stripeConfigured) {
            $fallback['stripe'] = [
                'code' => 'stripe',
                'name' => 'Credit / Debit Card — Stripe',
                'badge' => 'Stripe Secure',
                'description' => 'Enter your card securely via Stripe. You will not be charged until your laundry is weighed and priced.',
                'requires_online_gateway' => true,
                'is_configured' => true,
                'environment' => 'sandbox',
                'sort_order' => 1,
                'enable_saved_cards' => true,
                'instructions' => '',
            ];
        }
        $fallback['cod'] = [
            'code' => 'cod',
            'name' => 'Cash on Delivery',
            'badge' => 'Pay on Delivery',
            'description' => 'Pay by cash or POS card terminal upon collection or delivery after your items are counted and cleaned.',
            'requires_online_gateway' => false,
            'is_configured' => true,
            'environment' => 'none',
            'sort_order' => 3,
            'enable_saved_cards' => false,
            'instructions' => '',
        ];

        return $fallback;
    }

    /**
     * Calculate authoritative booking pricing from database service records, store fee rules, and validated driver tip.
     * Never trusts browser-submitted service prices.
     */
    public function calculateBookingPricing(array $booking): array
    {
        $catalog = $this->getBookingServiceCatalog()->keyBy('id');

        return app(\App\Services\BookingPricingService::class)->calculate($booking, $catalog);
    }

    /**
     * STEP 5: Display Final Review & Confirm Page.
     * Server-side guarded: requires Steps 1, 2, 3, and 4 to be completed.
     */
    public function showConfirmStep(Request $request): View|RedirectResponse
    {
        $booking = $request->session()->get('booking', []);
        if ($redirect = $this->enforceStepPrerequisites($booking, 5)) {
            return $redirect;
        }

        $request->session()->forget('booking_return_to_confirm');

        $pricing = $this->calculateBookingPricing($booking);
        // Keep verified service data synchronized in session
        $booking['services'] = $pricing['verified_services'];
        $booking['pricing'] = $pricing;
        $request->session()->put('booking', $booking);

        $paymentMethods = self::getSupportedPaymentMethods();
        if (! empty($booking['payment_method']) && ! array_key_exists($booking['payment_method'], $paymentMethods)) {
            unset($booking['payment_method']);
            $request->session()->put('booking', $booking);
        }

        $stripeService = app(StripePaymentService::class);
        $savedCards = $request->user()
            ? CustomerPaymentMethod::where('user_id', $request->user()->id)->orderByDesc('is_default')->orderByDesc('id')->get()
            : collect();

        return view('booking.confirm', [
            'booking' => $booking,
            'pricing' => $pricing,
            'tipOptions' => self::getDriverTipOptions(),
            'paymentMethods' => $paymentMethods,
            'stripeConfigured' => $stripeService->isActive(),
            'stripePublishableKey' => $stripeService->publishableKey(),
            'savedCards' => $savedCards,
            'currentBookingStep' => 5,
            'bookingSteps' => self::evaluateBookingSteps($booking, 5),
        ]);
    }

    /**
     * AJAX / Form Endpoint on Step 5: Update Driver Tip, Promo Code attempt, Repeat Order preference, or Payment Method.
     */
    public function updateConfirmOptions(Request $request): RedirectResponse|JsonResponse|\Illuminate\Http\Response
    {
        $booking = $request->session()->get('booking', []);
        if ($this->isSessionBookingLocked($booking)) {
            return $this->rejectLockedBookingMutation($request, $booking);
        }
        if ($redirect = $this->enforceStepPrerequisites($booking, 5)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please complete earlier booking steps first.'], 422);
            }

            return $redirect;
        }

        $action = (string) $request->input('action', 'update_all');
        $confirmOptions = $booking['confirm_options'] ?? [
            'tip_option' => '0',
            'tip_amount' => 0.0,
            'promo_code' => null,
            'payment_method' => null,
        ];

        // 1. Handle Promo Code validation (No promo code table exists in DB; report clearly without inventing fake discounts)
        if ($action === 'apply_promo') {
            $rawCode = strtoupper(trim((string) $request->input('promo_code', '')));
            if ($rawCode === '') {
                $msg = 'Please enter a promo code to apply.';
            } else {
                $msg = 'Promo code "' . $rawCode . '" is invalid, expired, or promotions are not currently active for this booking.';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'promo_available' => false,
                    'message' => $msg,
                    'errors' => ['promo_code' => [$msg]],
                    'pricing' => $this->calculateBookingPricing($booking),
                ], 422);
            }

            return back()->withErrors(['promo_code' => $msg])->withInput();
        }

        if ($action === 'remove_promo') {
            $confirmOptions['promo_code'] = null;
            $booking['confirm_options'] = $confirmOptions;
            $pricing = $this->calculateBookingPricing($booking);
            $booking['pricing'] = $pricing;
            $request->session()->put('booking', $booking);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Promo code removed.',
                    'pricing' => $pricing,
                    'booking' => $booking,
                ]);
            }

            return back();
        }

        // 2. Handle Driver Tip update
        if ($request->has('tip_option') || $action === 'update_tip') {
            $validatedTip = $request->validate([
                'tip_option' => ['required', Rule::in(['0', '50', '100', '150', 'custom'])],
                'custom_tip_amount' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            ], [
                'tip_option.in' => 'Please select a valid driver tip option.',
                'custom_tip_amount.numeric' => 'Custom tip must be a valid non-negative amount.',
                'custom_tip_amount.min' => 'Driver tip cannot be negative.',
                'custom_tip_amount.max' => 'Maximum allowed driver tip is BDT 5,000.00.',
            ]);

            $tipOpt = (string) $validatedTip['tip_option'];
            if ($tipOpt === 'custom') {
                $tipAmt = round((float) ($validatedTip['custom_tip_amount'] ?? 0.0), 2);
            } else {
                $tipAmt = round((float) $tipOpt, 2);
            }

            $confirmOptions['tip_option'] = $tipOpt;
            $confirmOptions['tip_amount'] = $tipAmt;
        }

        // 3. Handle Repeat Order / Subscribe preference synchronized with Step 3 (booking.schedule)
        if ($request->has('repeat_frequency') || $action === 'update_repeat') {
            $validatedRepeat = $request->validate([
                'repeat_frequency' => ['nullable', Rule::in(['none', 'weekly', 'bi_weekly', 'monthly'])],
            ]);

            $freq = (string) ($validatedRepeat['repeat_frequency'] ?? 'none');
            if ($freq === 'none' || $freq === '') {
                $booking['schedule']['repeat_order'] = false;
                $booking['schedule']['repeat_frequency'] = null;
            } else {
                $booking['schedule']['repeat_order'] = true;
                $booking['schedule']['repeat_frequency'] = $freq;
            }
        }

        // 4. Handle Payment Method selection
        if ($request->has('payment_method') || $action === 'update_payment') {
            $supportedKeys = array_keys(self::getSupportedPaymentMethods());
            $validatedPay = $request->validate([
                'payment_method' => ['required', Rule::in($supportedKeys)],
            ], [
                'payment_method.required' => 'Please select a supported payment method.',
                'payment_method.in' => 'Selected payment method is not supported.',
            ]);

            $methodKey = $validatedPay['payment_method'];
            $methodMeta = self::getSupportedPaymentMethods()[$methodKey];
            $confirmOptions['payment_method'] = $methodKey;
            $confirmOptions['payment_method_name'] = $methodMeta['name'];
            $confirmOptions['payment_method_badge'] = $methodMeta['badge'];
        }

        $booking['confirm_options'] = $confirmOptions;
        $pricing = $this->calculateBookingPricing($booking);
        $booking['pricing'] = $pricing;
        $request->session()->put('booking', $booking);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Booking options updated.',
                'confirm_options' => $confirmOptions,
                'schedule' => $booking['schedule'] ?? [],
                'pricing' => $pricing,
            ]);
        }

        return redirect()->route('booking.confirm');
    }

    /**
     * Store STEP 5 confirmation state & payment method in session after revalidating all 5 steps against the database.
     * Prevents duplicate submission and never marks a booking as paid before verified payment confirmation.
     */
    public function storeConfirmStep(Request $request): RedirectResponse|JsonResponse
    {
        $booking = $request->session()->get('booking', []);
        if ($redirect = $this->enforceStepPrerequisites($booking, 5)) {
            return $redirect;
        }

        $supportedPaymentMethods = self::getSupportedPaymentMethods();
        $supportedPaymentKeys = array_keys($supportedPaymentMethods);

        if ($supportedPaymentKeys === []) {
            return back()->withErrors([
                'payment_method' => 'No payment methods are currently available. Please contact support.',
            ])->withInput();
        }

        $incomingPaymentMethod = $request->input('payment_method');
        $request->merge(['payment_method' => $incomingPaymentMethod]);

        $validated = $request->validate([
            'payment_method' => ['required', Rule::in($supportedPaymentKeys)],
            'tip_option' => ['nullable', Rule::in(['0', '50', '100', '150', 'custom'])],
            'custom_tip_amount' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'repeat_frequency' => ['nullable', Rule::in(['none', 'weekly', 'bi_weekly', 'monthly'])],
            'stripe_setup_intent_id' => ['nullable', 'string', 'max:120'],
            'stripe_payment_method_id' => ['nullable', 'string', 'max:120'],
            'saved_payment_method_id' => ['nullable', 'integer'],
            'save_card' => ['nullable', 'boolean'],
            'stripe_consent' => ['nullable', 'boolean'],
        ], [
            'payment_method.required' => 'Please select a payment method before confirming your booking.',
            'payment_method.in' => 'Please select a valid supported payment method.',
            'custom_tip_amount.max' => 'Maximum allowed driver tip is BDT 5,000.00.',
        ]);

        $methodKey = $validated['payment_method'];
        $stripeCustomerId = $booking['stripe_customer_id'] ?? null;
        $stripePaymentMethodId = null;
        $stripeSetupIntentId = null;
        $consentAccepted = false;
        $consentTimestamp = null;
        $consentText = null;
        $paymentStatus = 'pending_item_count';

        if ($methodKey === 'paypal') {
            $paypalService = app(\App\Services\PayPalPaymentService::class);
            if (! $paypalService->isActive()) {
                return back()->withErrors(['payment_method' => 'PayPal payment gateway is currently unavailable. Please select another payment method.'])->withInput();
            }
            $paymentStatus = 'awaiting_final_amount';
        } elseif ($methodKey === 'stripe') {
            $stripeService = app(StripePaymentService::class);
            if (! $stripeService->isActive()) {
                return back()->withErrors(['payment_method' => 'Stripe payment gateway is currently unavailable. Please select another payment method.'])->withInput();
            }

            if (! empty($validated['saved_payment_method_id'])) {
                $user = $request->user();
                $savedPm = $user
                    ? CustomerPaymentMethod::where('id', $validated['saved_payment_method_id'])->where('user_id', $user->id)->first()
                    : null;

                if (! $savedPm) {
                    return back()->withErrors(['payment_method' => 'Selected saved card was not found or does not belong to your account.'])->withInput();
                }

                $consentAccepted = $request->boolean('stripe_consent', true);
                $consentTimestamp = now();
                $consentText = 'Customer authorized future off-session charging of the verified final laundry amount after collection, weighing, and item inspection.';
                $stripeCustomerId = $savedPm->stripe_customer_id;
                $stripePaymentMethodId = $savedPm->stripe_payment_method_id;
                $paymentStatus = 'authorized_for_later_charge';
            } elseif (! empty($validated['stripe_setup_intent_id']) && ! empty($validated['stripe_payment_method_id'])) {
                $consentAccepted = $request->boolean('stripe_consent', true);
                $consentTimestamp = now();
                $consentText = 'Customer authorized future off-session charging of the verified final laundry amount after collection, weighing, and item inspection.';
                $stripeSetupIntentId = trim($validated['stripe_setup_intent_id']);
                $stripePaymentMethodId = trim($validated['stripe_payment_method_id']);
                $paymentStatus = 'authorized_for_later_charge';

                if ($request->boolean('save_card') && $request->user()) {
                    try {
                        $meta = $stripeService->retrievePaymentMethod($stripePaymentMethodId);
                        CustomerPaymentMethod::updateOrCreate(
                            ['stripe_payment_method_id' => $meta['id']],
                            [
                                'user_id' => $request->user()->id,
                                'customer_email' => (string) ($booking['details']['customer_email'] ?? $request->user()->email),
                                'stripe_customer_id' => $meta['customer'] ?: (string) $stripeCustomerId,
                                'card_brand' => $meta['brand'],
                                'card_last4' => $meta['last4'],
                                'card_exp_month' => $meta['exp_month'],
                                'card_exp_year' => $meta['exp_year'],
                                'consent_timestamp' => $consentTimestamp,
                            ]
                        );
                    } catch (\Throwable $e) {
                        // Do not block booking if metadata sync fails; card is still attached to SetupIntent
                    }
                }
            } else {
                // Simplified Confirm flow: customer selects Stripe payment mode and proceeds immediately
                $paymentStatus = 'awaiting_final_amount';
            }
        }

        $confirmOptions = $booking['confirm_options'] ?? [];
        if (isset($validated['tip_option'])) {
            $tipOpt = (string) $validated['tip_option'];
            $tipAmt = $tipOpt === 'custom'
                ? round((float) ($validated['custom_tip_amount'] ?? ($confirmOptions['tip_amount'] ?? 0.0)), 2)
                : round((float) $tipOpt, 2);
            $confirmOptions['tip_option'] = $tipOpt;
            $confirmOptions['tip_amount'] = $tipAmt;
        }

        if (isset($validated['repeat_frequency'])) {
            $freq = (string) $validated['repeat_frequency'];
            if ($freq === 'none' || $freq === '') {
                $booking['schedule']['repeat_order'] = false;
                $booking['schedule']['repeat_frequency'] = null;
            } else {
                $booking['schedule']['repeat_order'] = true;
                $booking['schedule']['repeat_frequency'] = $freq;
            }
        }

        $methodKey = $validated['payment_method'];
        $methodMeta = $supportedPaymentMethods[$methodKey];
        $confirmOptions['payment_method'] = $methodKey;
        $confirmOptions['payment_method_name'] = $methodMeta['name'];
        $confirmOptions['payment_method_badge'] = $methodMeta['badge'];

        $booking['confirm_options'] = $confirmOptions;

        // Revalidate selected services against database and recalculate pricing on the server
        $pricing = $this->calculateBookingPricing($booking);
        if (empty($pricing['verified_services'])) {
            return redirect()->route('booking.services')
                ->with('booking_step_warning', 'Please select at least one valid laundry service.');
        }

        $booking['services'] = $pricing['verified_services'];
        $booking['pricing'] = $pricing;

        // Idempotent booking fingerprint & key to prevent duplicate booking creation on repeated clicks/retries
        $fingerprintPayload = json_encode([
            'services' => collect($booking['services'])->pluck('service_id')->all(),
            'address' => $booking['address']['selected_address'] ?? '',
            'room' => $booking['address']['room_number'] ?? '',
            'col_date' => $booking['schedule']['collection_date'] ?? '',
            'col_slot' => $booking['schedule']['collection_slot'] ?? '',
            'del_date' => $booking['schedule']['delivery_date'] ?? '',
            'del_slot' => $booking['schedule']['delivery_slot'] ?? '',
            'email' => $booking['details']['customer_email'] ?? '',
            'phone' => $booking['details']['customer_phone'] ?? '',
        ]);
        $currentFingerprint = sha1((string) $fingerprintPayload);
        $idempotencyKey = sha1($currentFingerprint.'|'.$methodKey.'|'.session()->getId());
        $details = $booking['details'] ?? [];

        $bookingModel = \Illuminate\Support\Facades\DB::transaction(function () use (
            $request,
            $booking,
            $pricing,
            $details,
            $methodKey,
            $methodMeta,
            $paymentStatus,
            $idempotencyKey,
            $stripeCustomerId,
            $stripePaymentMethodId,
            $stripeSetupIntentId,
            $consentAccepted,
            $consentTimestamp,
            $consentText
        ) {
            // Return existing confirmed booking if already created for this idempotency key or locked reference
            $existing = Booking::where('idempotency_key', $idempotencyKey)->first();
            if (! $existing && ! empty($booking['reference'])) {
                $existing = Booking::where('reference', $booking['reference'])
                    ->whereNotNull('confirmed_at')
                    ->first();
            }

            if ($existing) {
                return $existing;
            }

            $reference = Booking::generateUniqueReference();
            $currency = strtoupper((string) ($pricing['currency'] ?? currencyCode()));

            $created = Booking::create([
                'reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $request->user()?->id,
                'customer_name' => (string) ($details['customer_name'] ?? trim(($details['first_name'] ?? '').' '.($details['last_name'] ?? ''))),
                'customer_email' => (string) ($details['customer_email'] ?? ''),
                'customer_phone' => (string) ($details['customer_phone'] ?? ''),
                'customer_type' => (string) ($details['customer_type'] ?? 'individual'),
                'address_snapshot' => $booking['address'] ?? [],
                'schedule_snapshot' => $booking['schedule'] ?? [],
                'services_snapshot' => $booking['services'] ?? [],
                'contact_snapshot' => $details,
                'payment_gateway' => $methodKey,
                'payment_status' => $paymentStatus,
                'booking_status' => Booking::STATUS_CONFIRMED,
                'is_locked' => true,
                'currency' => $currency,
                'estimated_amount_minor' => (int) ($pricing['estimated_total_minor'] ?? round(((float) ($pricing['estimated_total'] ?? 0)) * 100)),
                'service_fee_minor' => (int) ($pricing['service_fee_minor'] ?? 0),
                'collection_delivery_fee_minor' => (int) ($pricing['collection_delivery_fee_minor'] ?? 0),
                'fee_snapshot' => $pricing['fee_snapshot'] ?? null,
                'payment_snapshot' => [
                    'payment_method' => $methodKey,
                    'payment_method_name' => $methodMeta['name'],
                    'payment_method_badge' => $methodMeta['badge'],
                    'payment_status' => $paymentStatus,
                ],
                'tip_amount_minor' => (int) ($pricing['tip_minor'] ?? round(((float) ($pricing['tip_amount'] ?? 0)) * 100)),
                'stripe_customer_id' => $stripeCustomerId,
                'stripe_payment_method_id' => $stripePaymentMethodId,
                'stripe_setup_intent_id' => $stripeSetupIntentId,
                'consent_accepted' => $consentAccepted,
                'consent_timestamp' => $consentTimestamp,
                'consent_text' => $consentText,
                'confirmed_at' => now(),
            ]);

            \App\Models\BookingAuditLog::create([
                'booking_id' => $created->id,
                'actor_type' => $request->user() ? 'customer' : 'guest',
                'actor_id' => $request->user()?->id,
                'actor_name' => $created->customer_name,
                'action' => 'confirmed',
                'summary' => "Booking {$created->reference} confirmed and permanently locked via {$methodKey}.",
                'changes' => [
                    'payment_gateway' => $methodKey,
                    'payment_status' => $paymentStatus,
                    'booking_status' => Booking::STATUS_CONFIRMED,
                ],
                'ip_address' => $request->ip(),
            ]);

            return $created;
        });

        $booking['reference'] = $bookingModel->reference;
        $booking['booking_fingerprint'] = $currentFingerprint;
        $booking['confirmed_at'] = $bookingModel->confirmed_at?->toDateTimeString() ?? now()->toDateTimeString();
        $booking['is_locked'] = true;
        $booking['payment_status'] = $bookingModel->payment_status;
        $request->session()->put('booking', $booking);
        $request->session()->put('last_confirmed_booking_reference', $bookingModel->reference);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'booking' => $booking,
                'pricing' => $pricing,
                'reference' => $bookingModel->reference,
                'redirect' => route('booking.success', ['reference' => $bookingModel->reference]),
            ]);
        }

        return redirect()
            ->route('booking.success', ['reference' => $bookingModel->reference])
            ->with(
                'booking_confirmed_success',
                'Your laundry booking ('.$bookingModel->reference.') is confirmed with '.$methodMeta['name'].'!'
            );
    }
}
