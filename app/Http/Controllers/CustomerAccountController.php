<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CustomerAddress;
use App\Models\CustomerPaymentMethod;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerAccountController extends Controller
{
    public function profile(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
            'countryList' => \App\Support\CountryDialCodes::all(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $allowedDialCodes = collect(\App\Support\CountryDialCodes::all())->pluck('dial')->unique()->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'country_code' => ['required', 'string', Rule::in($allowedDialCodes)],
            'phone' => ['required', 'string', 'regex:/^[0-9\s\-]{6,20}$/'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
        ]);

        $user->update([
            'name' => trim($validated['name']),
            'country_code' => $validated['country_code'],
            'phone' => preg_replace('/\s+/', '', trim($validated['phone'])),
            'gender' => $validated['gender'] ?: null,
            'profile_completed_at' => $user->profile_completed_at ?? now(),
        ]);

        return redirect()->route('account.profile')->with('success', 'Your profile has been updated.');
    }

    public function claimGuestOrder(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
        ]);

        $ref = strtoupper(trim($validated['reference']));
        $booking = Booking::where('reference', $ref)
            ->whereNull('user_id')
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($user->email)])
            ->whereNotNull('confirmed_at')
            ->first();

        if (! $booking) {
            return back()->withErrors([
                'reference' => 'No unclaimed confirmed booking matched that reference for your verified email.',
            ]);
        }

        $booking->update(['user_id' => $user->id]);

        return redirect()->route('orders.index')->with('success', "Order {$booking->reference} has been linked to your account.");
    }

    public function addresses(Request $request): View
    {
        $addresses = $request->user()->addresses()->get();

        return view('account.addresses', [
            'addresses' => $addresses,
        ]);
    }

    public function createAddress(Request $request): View
    {
        return view('account.address-form', [
            'address' => null,
            'isEditMode' => false,
            'returnTo' => $request->query('return_to'),
        ]);
    }

    public function editAddress(Request $request, CustomerAddress $address): View
    {
        Gate::forUser($request->user())->authorize('view', $address);

        return view('account.address-form', [
            'address' => $address,
            'isEditMode' => true,
            'returnTo' => $request->query('return_to'),
        ]);
    }

    protected function applyAddressToBookingSession(Request $request, CustomerAddress $address): void
    {
        $booking = $request->session()->get('booking', []);
        $isHotel = $address->address_type === 'hotel';

        $booking['address'] = [
            'saved_customer_address_id' => $address->id,
            'collection_mode' => $isHotel ? 'hotel' : 'home_office',
            'postcode' => $address->postcode ?? '',
            'selected_address' => $isHotel ? ($address->hotel_address ?? '') : ($address->formatted_address ?? ''),
            'manual_address' => (! $isHotel && empty($address->postcode)) ? ($address->formatted_address ?? '') : '',
            'is_manual' => ! $isHotel && empty($address->postcode),
            'address_type' => strtoupper((string) $address->address_type),
            'address_details' => $address->address_details ?? '',
            'hotel_name' => $address->hotel_name ?? '',
            'hotel_address' => $address->hotel_address ?? '',
            'room_number' => $address->room_number ?? '',
            'hotel_collection_type' => $address->hotel_collection_type ?? null,
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($address->hasContactDetails()) {
            $booking['address_contact'] = [
                'customer_type' => $address->contact_type ?: 'individual',
                'first_name' => $address->contact_first_name ?? '',
                'last_name' => $address->contact_last_name ?? '',
                'company_name' => $address->contact_company_name ?? '',
                'tax_number' => $address->contact_tax_number ?? '',
                'country_iso' => $address->contact_country_iso ?? '',
                'country_code' => $address->contact_dialing_code ?: '+44',
                'phone_number' => $address->contact_phone ?? '',
                'customer_email' => $address->contact_email ?? '',
            ];
        }

        $request->session()->put('booking', $booking);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $this->validateAddressPayload($request);

        $created = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $request, $validated) {
            if ($request->boolean('is_default')) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create(array_merge($validated, [
                'is_default' => $request->boolean('is_default') || ! $user->addresses()->exists(),
            ]));
        });

        if ($request->input('return_to') === 'booking_address') {
            $this->applyAddressToBookingSession($request, $created);

            return redirect()->route('booking.address')->with('booking_address_saved_notice', 'Saved address added and selected for your booking.');
        }

        return redirect()->route('account.addresses')->with('success', 'Address saved to your Address Book.');
    }

    public function updateAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $address);
        $validated = $this->validateAddressPayload($request);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $address, $validated) {
            if ($request->boolean('is_default')) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
                $validated['is_default'] = true;
            } else {
                $validated['is_default'] = $address->is_default;
            }

            $address->update($validated);
        });

        if ((int) $request->session()->get('booking.address.saved_customer_address_id') === (int) $address->id) {
            $this->applyAddressToBookingSession($request, $address->fresh());
        }

        if ($request->input('return_to') === 'booking_address') {
            return redirect()->route('booking.address')->with('booking_address_saved_notice', 'Saved address updated.');
        }

        return redirect()->route('account.addresses')->with('success', 'Address updated successfully.');
    }

    public function setDefaultAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $address);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return redirect()->route('account.addresses')->with('success', 'Default address updated.');
    }

    public function destroyAddress(Request $request, CustomerAddress $address): RedirectResponse|JsonResponse
    {
        Gate::forUser($request->user())->authorize('delete', $address);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = $request->user()->addresses()->first();
                $next?->update(['is_default' => true]);
            }
        });

        $clearedSelected = false;
        if ((int) $request->session()->get('booking.address.saved_customer_address_id') === (int) $address->id) {
            $request->session()->forget('booking.address');
            $clearedSelected = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Saved address removed from your Address Book.',
                'cleared_selected' => $clearedSelected,
            ]);
        }

        return redirect()->route('account.addresses')->with('success', 'Address removed from your Address Book.');
    }

    protected function validateContactPayload(Request $request): array
    {
        if (! $request->has('contact_type') && ! $request->has('contact_first_name')) {
            return [];
        }

        $allowedDialCodes = collect(\App\Support\CountryDialCodes::all())->pluck('dial')->unique()->all();
        $contactType = strtolower((string) $request->input('contact_type', 'individual'));

        $validated = $request->validate([
            'contact_type' => ['required', Rule::in(['individual', 'company'])],
            'contact_first_name' => ['required', 'string', 'max:80'],
            'contact_last_name' => ['required', 'string', 'max:80'],
            'contact_company_name' => [$contactType === 'company' ? 'required' : 'nullable', 'string', 'max:150'],
            'contact_tax_number' => ['nullable', 'string', 'max:80'],
            'contact_country_iso' => ['nullable', 'string', 'max:4'],
            'contact_dialing_code' => ['required', 'string', Rule::in($allowedDialCodes)],
            'contact_phone' => ['required', 'string', 'regex:/^[0-9\s\-]{6,20}$/'],
            'contact_email' => ['required', 'email', 'max:160'],
        ]);

        return [
            'contact_type' => $validated['contact_type'],
            'contact_first_name' => trim($validated['contact_first_name']),
            'contact_last_name' => trim($validated['contact_last_name']),
            'contact_company_name' => $contactType === 'company' ? trim((string) ($validated['contact_company_name'] ?? '')) : null,
            'contact_tax_number' => $contactType === 'company' ? trim((string) ($validated['contact_tax_number'] ?? '')) : null,
            'contact_country_iso' => ! empty($validated['contact_country_iso']) ? strtoupper(trim($validated['contact_country_iso'])) : null,
            'contact_dialing_code' => $validated['contact_dialing_code'],
            'contact_phone' => preg_replace('/\s+/', '', trim($validated['contact_phone'])),
            'contact_email' => strtolower(trim($validated['contact_email'])),
        ];
    }

    protected function validateAddressPayload(Request $request): array
    {
        $collectionMode = strtolower((string) $request->input('collection_mode', ''));
        $rawType = strtolower((string) $request->input('address_type', 'home'));
        $isHotelScreen = $collectionMode === 'hotel' || ($collectionMode === '' && $rawType === 'hotel');
        $contactFields = $this->validateContactPayload($request);

        if ($isHotelScreen) {
            $validated = $request->validate([
                'label' => ['nullable', 'string', 'max:80'],
                'hotel_name' => ['required', 'string', 'max:160'],
                'hotel_address' => ['required', 'string', 'max:255'],
                'room_number' => ['required', 'string', 'max:40'],
                'hotel_collection_type' => ['required', Rule::in(['use_reception', 'meet_driver'])],
                'address_details' => ['nullable', 'string', 'max:255'],
            ]);

            return array_merge([
                'address_type' => 'hotel',
                'label' => trim((string) ($validated['label'] ?? '')) ?: 'Hotel',
                'hotel_name' => strtoupper(trim($validated['hotel_name'])),
                'hotel_address' => strtoupper(trim($validated['hotel_address'])),
                'room_number' => strtoupper(trim($validated['room_number'])),
                'hotel_collection_type' => $validated['hotel_collection_type'],
                'postcode' => null,
                'formatted_address' => strtoupper(trim($validated['hotel_address'])),
                'address_details' => strtoupper(trim((string) ($validated['address_details'] ?? ''))),
            ], $contactFields);
        }

        $isManual = $request->boolean('is_manual');
        $resolvedAddress = trim((string) ($request->input('selected_address')
            ?: $request->input('manual_address')
            ?: $request->input('formatted_address')));

        $request->merge([
            'address_type_norm' => in_array($rawType, ['home', 'office', 'hotel'], true) ? $rawType : 'home',
            'resolved_address' => $resolvedAddress,
        ]);

        $validated = $request->validate([
            'address_type_norm' => ['required', Rule::in(['home', 'office', 'hotel'])],
            'label' => ['nullable', 'string', 'max:80'],
            'postcode' => [$isManual ? 'nullable' : 'required', 'string', 'max:20'],
            'resolved_address' => ['required', 'string', 'max:255'],
            'address_details' => ['nullable', 'string', 'max:255'],
            'room_number' => [$rawType === 'hotel' ? 'required' : 'nullable', 'string', 'max:40'],
        ], [
            'resolved_address.required' => 'Please select an address from the list or enter your address manually.',
        ]);

        return array_merge([
            'address_type' => $validated['address_type_norm'],
            'label' => trim((string) ($validated['label'] ?? '')) ?: ucfirst($validated['address_type_norm']),
            'postcode' => ! empty($validated['postcode']) ? strtoupper(trim($validated['postcode'])) : null,
            'formatted_address' => strtoupper(trim($validated['resolved_address'])),
            'address_details' => strtoupper(trim((string) ($validated['address_details'] ?? ''))),
            'hotel_name' => null,
            'hotel_address' => null,
            'room_number' => ! empty($validated['room_number']) ? strtoupper(trim($validated['room_number'])) : null,
            'hotel_collection_type' => null,
        ], $contactFields);
    }

    public function paymentMethods(Request $request, StripePaymentService $stripe): View
    {
        $cards = $request->user()->paymentMethods()->get();
        $stripeActive = $stripe->isActive() && $stripe->savedCardsEnabled();

        return view('account.payment-methods', [
            'cards' => $cards,
            'stripeActive' => $stripeActive,
            'stripePublishableKey' => $stripeActive ? $stripe->publishableKey() : null,
        ]);
    }

    public function createSetupIntent(Request $request, StripePaymentService $stripe): JsonResponse
    {
        if (! $stripe->isActive() || ! $stripe->savedCardsEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe saved cards are currently unavailable.',
            ], 503);
        }

        $user = $request->user();

        try {
            $customerId = $stripe->getOrCreateCustomer($user->email, $user->name, $user->id);
            $setup = $stripe->createSetupIntent($customerId, [
                'user_id' => (string) $user->id,
                'customer_email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $setup['client_secret'],
                'setup_intent_id' => $setup['id'],
            ]);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to initialize Stripe card setup.',
            ], 500);
        }
    }

    public function attachCard(Request $request, StripePaymentService $stripe): JsonResponse
    {
        if (! $stripe->isActive() || ! $stripe->savedCardsEnabled()) {
            return response()->json(['success' => false, 'message' => 'Stripe is currently unavailable.'], 503);
        }

        $validated = $request->validate([
            'stripe_payment_method_id' => ['required', 'string', 'max:120'],
            'consent' => ['required', 'accepted'],
        ]);

        $user = $request->user();

        try {
            $meta = $stripe->retrievePaymentMethod($validated['stripe_payment_method_id']);
            $hasAny = $user->paymentMethods()->exists();

            $card = CustomerPaymentMethod::updateOrCreate(
                ['stripe_payment_method_id' => $meta['id']],
                [
                    'user_id' => $user->id,
                    'customer_email' => $user->email,
                    'stripe_customer_id' => $meta['customer'] ?: $stripe->getOrCreateCustomer($user->email, $user->name, $user->id),
                    'card_brand' => $meta['brand'],
                    'card_last4' => $meta['last4'],
                    'card_exp_month' => $meta['exp_month'],
                    'card_exp_year' => $meta['exp_year'],
                    'is_default' => ! $hasAny,
                    'consent_timestamp' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'card' => [
                    'id' => $card->id,
                    'display_label' => $card->display_label,
                ],
            ]);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to save card details.',
            ], 422);
        }
    }

    public function setDefaultCard(Request $request, CustomerPaymentMethod $paymentMethod): RedirectResponse
    {
        if ((int) $paymentMethod->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $request->user()->paymentMethods()->update(['is_default' => false]);
        $paymentMethod->update(['is_default' => true]);

        return redirect()->route('account.payment-methods')->with('success', 'Default payment method updated.');
    }

    public function destroyCard(Request $request, CustomerPaymentMethod $paymentMethod): RedirectResponse
    {
        if ((int) $paymentMethod->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $wasDefault = $paymentMethod->is_default;
        $paymentMethod->delete();

        if ($wasDefault) {
            $next = $request->user()->paymentMethods()->first();
            $next?->update(['is_default' => true]);
        }

        return redirect()->route('account.payment-methods')->with('success', 'Saved card removed.');
    }
}
