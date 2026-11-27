<?php

use App\Http\Controllers\BookingAddressController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerBookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PayPalPaymentController;
use App\Http\Controllers\StripePaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/api/home/search', [HomeController::class, 'search'])
    ->middleware('throttle:60,1')
    ->name('home.search');
Route::get('/about', [\App\Http\Controllers\PublicPageController::class, 'about'])->name('about');
Route::get('/contact', [\App\Http\Controllers\PublicPageController::class, 'contact'])->name('contact');
Route::post('/contact/enquiry', [\App\Http\Controllers\PublicPageController::class, 'submitEnquiry'])
    ->middleware('throttle:10,1')
    ->name('contact.enquiry.store');

Route::get('/join-delivery-person', [\App\Http\Controllers\PublicDeliveryPersonRecruitmentController::class, 'index'])
    ->name('join-delivery-person');
Route::post('/join-delivery-person/apply', [\App\Http\Controllers\PublicDeliveryPersonRecruitmentController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('join-delivery-person.apply');
Route::get('/delivery-person/application-status', [\App\Http\Controllers\PublicDeliveryPersonRecruitmentController::class, 'statusPage'])
    ->name('delivery-person.application-status');
Route::post('/delivery-person/application-status/lookup', [\App\Http\Controllers\PublicDeliveryPersonRecruitmentController::class, 'lookupStatus'])
    ->middleware('throttle:20,1')
    ->name('delivery-person.application-status.lookup');
Route::post('/delivery-person/application-status/resubmit', [\App\Http\Controllers\PublicDeliveryPersonRecruitmentController::class, 'resubmitInfo'])
    ->middleware('throttle:10,1')
    ->name('delivery-person.application-status.resubmit');

Route::get('/services', function (Request $request) {
    $filters = $request->validate([
        'category' => ['nullable', 'string', 'max:255'],
        'item' => ['nullable', 'string', 'max:20'],
        'q' => ['nullable', 'string', 'max:100'],
        'sort' => ['nullable', Rule::in(['name', 'price-low', 'price-high'])],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $svcCms = app(\App\Services\ServicesPageContentService::class);
    $isPreviewDraft = $request->query('preview') === 'draft'
        && Auth::guard('admin')->check()
        && Auth::guard('admin')->user()?->can('manage-settings', 'admin');
    $servicesPayload = $svcCms->getPublicPayload($isPreviewDraft);
    $servicesSections = collect($servicesPayload['sections'] ?? [])->sortBy(fn ($s) => (int) ($s['order'] ?? 99))->values();
    $catalogCfg = $servicesPayload['catalog_config'] ?? [];
    $catalogOverrides = $catalogCfg['overrides'] ?? [];

    $categories = $svcCms->getVisibleActiveCategories($servicesPayload);
    $hasOtherServices = Service::query()->where('is_active', true)->whereNull('item_category_id')->whereNull('item_id')->exists();
    $selected = $filters['category'] ?? null;

    if ($selected === null && ! $hasOtherServices && $categories->isNotEmpty()) {
        return redirect()->route('services.index', array_merge($request->query(), ['category' => $categories->first()->slug]));
    }

    $category = $categories->firstWhere('slug', $selected);
    abort_if($selected !== null && $selected !== 'other-services' && ! $category, 404);

    $items = $category ? Item::query()->where('item_category_id', $category->id)->where('is_active', true)->orderBy('name')->get() : collect();
    $selectedItem = $filters['item'] ?? null;
    abort_if($selectedItem !== null && $selectedItem !== 'other' && ! $items->contains(fn ($item) => (string) $item->id === $selectedItem), 404);
    $search = trim($filters['q'] ?? '');
    $sort = $filters['sort'] ?? 'name';

    $hiddenServiceIds = collect($catalogOverrides)
        ->filter(fn ($ov) => array_key_exists('visible', $ov) && empty($ov['visible']))
        ->keys()
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    $query = Service::query()->with(['unit', 'item'])->where('is_active', true)
        ->when($hiddenServiceIds !== [], fn ($q) => $q->whereNotIn('id', $hiddenServiceIds))
        ->where(fn ($query) => $query->whereNull('item_id')->orWhereHas('item', fn ($query) => $query->where('is_active', true)))
        ->when($category, fn ($query) => $query->where(function ($query) use ($category) {
            $query->where('item_category_id', $category->id)
                ->orWhere(fn ($query) => $query->whereNull('item_category_id')->whereHas('item', fn ($query) => $query->where('item_category_id', $category->id)));
        }))
        ->when($selected === 'other-services', fn ($query) => $query->whereNull('item_category_id')->whereNull('item_id'))
        ->when($selected === null, fn ($query) => $query->whereNull('item_category_id')->whereNull('item_id'));

    $hasCategoryServices = (clone $query)->whereNull('item_id')->exists();

    $query->when($selectedItem === 'other', fn ($query) => $query->whereNull('item_id'))
        ->when($selectedItem !== null && $selectedItem !== 'other', fn ($query) => $query->where('item_id', $selectedItem))
        ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhereHas('item', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
        }));

    if ($sort === 'price-low' || $sort === 'price-high') {
        $query->orderBy('price', $sort === 'price-low' ? 'asc' : 'desc');
    }

    $perPage = in_array((int) ($catalogCfg['per_page'] ?? 12), [8, 12, 16, 24], true) ? (int) $catalogCfg['per_page'] : 12;
    $services = $query->orderBy('name')->orderBy('id')->paginate($perPage)->withQueryString();
    $featuredServices = $svcCms->getFeaturedServices($servicesPayload);

    $data = compact(
        'categories', 'category', 'selected', 'services', 'items', 'hasOtherServices',
        'selectedItem', 'search', 'sort', 'hasCategoryServices',
        'servicesPayload', 'servicesSections', 'featuredServices', 'isPreviewDraft', 'svcCms', 'catalogOverrides'
    );

    if ($request->expectsJson()) {
        return response()->json([
            'html' => view('services.results', $data)->render(),
            'summary' => view('services.category-summary', $data)->render(),
            'category' => $category?->slug ?? 'other-services',
            'title' => $category?->name ?? 'Other services',
            'total' => $services->total(),
        ]);
    }

    return view('services.index', $data);
})->name('services.index');

// Public Postcode & Hotel Search APIs (rate-limited)
Route::get('/api/addresses/search', [BookingAddressController::class, 'apiSearchAddresses'])
    ->middleware('throttle:60,1')
    ->name('api.addresses.search');
Route::get('/api/hotels/search', [BookingAddressController::class, 'searchHotels'])
    ->middleware('throttle:60,1')
    ->name('api.hotels.search');

// Booking Flow: 5-Step Connected Progress Workflow
Route::prefix('booking')->name('booking.')->group(function () {
    // Step 1: Select Services
    Route::get('/services', [BookingAddressController::class, 'showServicesStep'])->name('services');
    Route::post('/services', [BookingAddressController::class, 'storeServicesStep'])->name('services.store');

    // Step 2: Collection Address / Hotel
    Route::get('/address', [BookingAddressController::class, 'showAddressStep'])->name('address');
    Route::post('/address', [BookingAddressController::class, 'storeAddress'])->name('address.store');
    Route::get('/postcode-lookup', [BookingAddressController::class, 'lookupPostcode'])->middleware('throttle:60,1')->name('postcode.lookup');
    Route::get('/hotel-search', [BookingAddressController::class, 'searchHotels'])->middleware('throttle:60,1')->name('hotel.search');

    // Step 3: Schedule Collection & Delivery
    Route::get('/schedule', [BookingAddressController::class, 'showScheduleStep'])->name('schedule');
    Route::post('/schedule', [BookingAddressController::class, 'storeScheduleStep'])->name('schedule.store');
    Route::get('/available-dates', [BookingAddressController::class, 'getAvailableDates'])->middleware('throttle:60,1')->name('available-dates');
    Route::get('/available-slots', [BookingAddressController::class, 'getAvailableSlots'])->middleware('throttle:60,1')->name('available-slots');
    Route::get('/driver-instructions', [BookingAddressController::class, 'getDriverInstructions'])->middleware('throttle:60,1')->name('driver-instructions');

    // Step 4: Customer Details & Notes
    Route::get('/details', [BookingAddressController::class, 'showDetailsStep'])->name('details');
    Route::post('/details', [BookingAddressController::class, 'storeDetailsStep'])->name('details.store');

    // Step 5: Review & Confirm
    Route::get('/confirm', [BookingAddressController::class, 'showConfirmStep'])->name('confirm');
    Route::post('/confirm', [BookingAddressController::class, 'storeConfirmStep'])->name('confirm.store');
    Route::post('/confirm/options', [BookingAddressController::class, 'updateConfirmOptions'])->name('confirm.options');
    Route::get('/success/{reference}', [CustomerBookingController::class, 'success'])->name('success');

    // Stripe SetupIntent & Saved Cards
    Route::post('/stripe/setup-intent', [StripePaymentController::class, 'createSetupIntent'])
        ->middleware('throttle:30,1')
        ->name('stripe.setup-intent');
    Route::get('/stripe/saved-cards', [StripePaymentController::class, 'savedCards'])
        ->middleware('auth:web')
        ->name('stripe.saved-cards');
    Route::delete('/stripe/saved-cards/{paymentMethod}', [StripePaymentController::class, 'destroySavedCard'])
        ->middleware('auth:web')
        ->name('stripe.saved-cards.destroy');

    // Stripe Checkout Return & Cancel (signed URLs)
    Route::get('/stripe/bookings/{booking}/return', [StripePaymentController::class, 'checkoutReturn'])
        ->name('stripe.return');
    Route::get('/stripe/bookings/{booking}/cancel', [StripePaymentController::class, 'checkoutCancel'])
        ->middleware('signed')
        ->name('stripe.cancel');

    // PayPal Order & Capture
    Route::post('/paypal/bookings/{booking}/order', [PayPalPaymentController::class, 'createOrder'])
        ->middleware('throttle:30,1')
        ->name('paypal.order');
    Route::get('/paypal/bookings/{booking}/capture', [PayPalPaymentController::class, 'captureReturn'])
        ->name('paypal.capture');
});

// Stripe & PayPal Webhook Endpoints (CSRF-exempt; signature verified)
Route::post('/stripe/webhook', [StripePaymentController::class, 'webhook'])->name('stripe.webhook');
Route::post('/paypal/webhook', [PayPalPaymentController::class, 'webhook'])->name('paypal.webhook');

// Customer Order History & Read-Only Booking Details (modifications/deletions blocked)
Route::prefix('my-orders')->name('orders.')->group(function () {
    Route::get('/', [CustomerBookingController::class, 'index'])->middleware('auth')->name('index');
    Route::post('/lookup', [CustomerBookingController::class, 'lookup'])->middleware('throttle:20,1')->name('lookup');
    Route::get('/{reference}', [CustomerBookingController::class, 'show'])->name('show');
    Route::get('/{reference}/payment-status', [CustomerBookingController::class, 'paymentStatus'])->name('payment-status');
    Route::post('/{reference}/stripe/payment-intent', [CustomerBookingController::class, 'createStripePaymentIntent'])->middleware(['auth', 'throttle:20,1'])->name('stripe.payment-intent');
    Route::post('/{reference}/pay/stripe', [CustomerBookingController::class, 'payWithStripe'])->middleware(['auth', 'throttle:20,1'])->name('pay.stripe');
    Route::post('/{reference}/pay/paypal', [CustomerBookingController::class, 'payWithPayPal'])->middleware(['auth', 'throttle:20,1'])->name('pay.paypal');
    Route::post('/{reference}/claims', [\App\Http\Controllers\CustomerClaimController::class, 'store'])->middleware('throttle:15,1')->name('claims.store');
    Route::get('/claims/evidence/{evidence}', [\App\Http\Controllers\CustomerClaimController::class, 'downloadEvidence'])->name('claims.evidence');
    Route::match(['put', 'patch'], '/{reference}', [CustomerBookingController::class, 'rejectMutation'])->name('update');
    Route::delete('/{reference}', [CustomerBookingController::class, 'rejectMutation'])->name('destroy');
});

// Customer Email-OTP Authentication (Passwordless, web guard)
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
Route::post('/login/otp', [CustomerAuthController::class, 'sendOtp'])->name('customer.login.send-otp');
Route::get('/login/verify', [CustomerAuthController::class, 'showOtpForm'])->name('customer.otp.show');
Route::post('/login/verify', [CustomerAuthController::class, 'verifyOtp'])->name('customer.otp.verify');
Route::post('/login/resend', [CustomerAuthController::class, 'resendOtp'])->name('customer.otp.resend');
Route::get('/register/complete', [CustomerAuthController::class, 'showRegister'])->name('customer.register.show');
Route::post('/register/complete', [CustomerAuthController::class, 'completeRegister'])->name('customer.register.store');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

// Authenticated Customer Account Pages (inside main website layout)
Route::middleware('auth:web')->group(function () {
    Route::get('/profile', [CustomerAccountController::class, 'profile'])->name('account.profile');
    Route::post('/profile', [CustomerAccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/my-orders/claim', [CustomerAccountController::class, 'claimGuestOrder'])->name('orders.claim');

    Route::get('/address-book', [CustomerAccountController::class, 'addresses'])->name('account.addresses');
    Route::get('/address-book/create', [CustomerAccountController::class, 'createAddress'])->name('account.addresses.create');
    Route::post('/address-book', [CustomerAccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::get('/address-book/{address}/edit', [CustomerAccountController::class, 'editAddress'])->name('account.addresses.edit');
    Route::put('/address-book/{address}', [CustomerAccountController::class, 'updateAddress'])->name('account.addresses.update');
    Route::post('/address-book/{address}/default', [CustomerAccountController::class, 'setDefaultAddress'])->name('account.addresses.default');
    Route::delete('/address-book/{address}', [CustomerAccountController::class, 'destroyAddress'])->name('account.addresses.destroy');

    Route::get('/saved-payment-methods', [CustomerAccountController::class, 'paymentMethods'])->name('account.payment-methods');
    Route::post('/saved-payment-methods/setup-intent', [CustomerAccountController::class, 'createSetupIntent'])->name('account.payment-methods.setup-intent');
    Route::post('/saved-payment-methods/attach', [CustomerAccountController::class, 'attachCard'])->name('account.payment-methods.attach');
    Route::post('/saved-payment-methods/{paymentMethod}/default', [CustomerAccountController::class, 'setDefaultCard'])->name('account.payment-methods.default');
    Route::delete('/saved-payment-methods/{paymentMethod}', [CustomerAccountController::class, 'destroyCard'])->name('account.payment-methods.destroy');
});


