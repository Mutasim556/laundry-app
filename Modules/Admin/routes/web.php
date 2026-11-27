<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Auth\LoginController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\ProfileController;
use Modules\Admin\Http\Controllers\RoleController;

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Admin Routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    // Authenticated Admin Routes
    Route::middleware(['auth:admin', 'not_blocked:admin'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        // Admin Profile Routes
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Roles & Permissions Management
        Route::middleware('permission:view-roles,admin')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('roles/data', [RoleController::class, 'data'])->name('roles.data');
            Route::get('roles/{role}/users', [RoleController::class, 'users'])->name('roles.users');
        });
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:create-roles,admin')->name('roles.store');
        Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])->middleware('permission:edit-roles,admin')->name('roles.update');
        Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:edit-roles,admin')->name('roles.permissions.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete-roles,admin')->name('roles.destroy');

        // Admins Management
        Route::middleware('permission:view-admins,admin')->group(function () {
            Route::get('admins', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'index'])->name('admins.index');
            Route::get('admins/data', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'data'])->name('admins.data');
        });
        Route::post('admins', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'store'])->middleware('permission:create-admins,admin')->name('admins.store');
        Route::match(['put', 'patch'], 'admins/{admin}', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'update'])->middleware('permission:edit-admins,admin')->name('admins.update');
        Route::put('admins/{admin}/roles', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'updateRoles'])->middleware('permission:edit-admins,admin')->name('admins.roles.update');
        Route::patch('admins/{admin}/toggle-block', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'toggleBlock'])->middleware('permission:edit-admins,admin')->name('admins.toggle-block');
        Route::delete('admins/{admin}', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'destroy'])->middleware('permission:delete-admins,admin')->name('admins.destroy');

        // Users Management (Web Guard)
        Route::middleware('permission:view-users,admin')->group(function () {
            Route::get('users', [\Modules\Admin\Http\Controllers\UserController::class, 'index'])->name('users.index');
            Route::get('users/data', [\Modules\Admin\Http\Controllers\UserController::class, 'data'])->name('users.data');
        });
        Route::post('users', [\Modules\Admin\Http\Controllers\UserController::class, 'store'])->middleware('permission:create-users,admin')->name('users.store');
        Route::match(['put', 'patch'], 'users/{user}', [\Modules\Admin\Http\Controllers\UserController::class, 'update'])->middleware('permission:edit-users,admin')->name('users.update');
        Route::put('users/{user}/roles', [\Modules\Admin\Http\Controllers\UserController::class, 'updateRoles'])->middleware('permission:edit-users,admin')->name('users.roles.update');
        Route::patch('users/{user}/toggle-block', [\Modules\Admin\Http\Controllers\UserController::class, 'toggleBlock'])->middleware('permission:edit-users,admin')->name('users.toggle-block');
        Route::delete('users/{user}', [\Modules\Admin\Http\Controllers\UserController::class, 'destroy'])->middleware('permission:delete-users,admin')->name('users.destroy');

        // System & Branding Settings
        Route::middleware('permission:manage-settings,admin')->group(function () {
            Route::get('settings', [\Modules\Admin\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
            Route::match(['post', 'put', 'patch'], 'settings', [\Modules\Admin\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
            Route::delete('settings/{key}', [\Modules\Admin\Http\Controllers\SettingController::class, 'destroy'])->name('settings.destroy');

            // Website Manager -> Homepage Contents
            Route::prefix('website-manager/homepage')->name('website-manager.homepage.')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'index'])->name('index');
                Route::post('sections', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'saveSections'])->name('sections.save');
                Route::post('hero', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'saveHero'])->name('hero.save');
                Route::post('search', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'saveSearch'])->name('search.save');
                Route::post('categories', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'saveCategories'])->name('categories.save');
                Route::post('promo-banner', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'savePromoBanner'])->name('promo-banner.save');
                Route::post('services', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'saveServices'])->name('services.save');
                Route::post('seo', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'saveSeo'])->name('seo.save');
                Route::post('publish', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'publish'])->name('publish');
                Route::post('versions/{version}/restore', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'restoreVersion'])->name('versions.restore');
                Route::post('media', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'uploadMedia'])->name('media.upload');
                Route::delete('media/{media}', [\Modules\Admin\App\Http\Controllers\HomepageContentController::class, 'deleteMedia'])->name('media.delete');
            });

            // Website Manager -> Services Page Contents
            Route::prefix('website-manager/services')->name('website-manager.services.')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'index'])->name('index');
                Route::post('sections', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveSections'])->name('sections.save');
                Route::post('banner', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveBanner'])->name('banner.save');
                Route::post('search', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveSearch'])->name('search.save');
                Route::post('categories', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveCategories'])->name('categories.save');
                Route::post('catalog', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveCatalog'])->name('catalog.save');
                Route::post('featured', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveFeatured'])->name('featured.save');
                Route::post('cta', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveCta'])->name('cta.save');
                Route::post('seo', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'saveSeo'])->name('seo.save');
                Route::post('publish', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'publish'])->name('publish');
                Route::post('versions/{version}/restore', [\Modules\Admin\App\Http\Controllers\ServicesPageContentController::class, 'restoreVersion'])->name('versions.restore');
            });

            // Website Manager -> About Page Contents
            Route::prefix('website-manager/about')->name('website-manager.about.')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'index'])->name('index');
                Route::post('sections', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveSections'])->name('sections.save');
                Route::post('hero', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveHero'])->name('hero.save');
                Route::post('story', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveStory'])->name('story.save');
                Route::post('mission-vision', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveMissionVision'])->name('mission-vision.save');
                Route::post('benefits', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveBenefits'])->name('benefits.save');
                Route::post('how-it-works', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveHowItWorks'])->name('how-it-works.save');
                Route::post('stats', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveStats'])->name('stats.save');
                Route::post('cta', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveCta'])->name('cta.save');
                Route::post('seo', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'saveSeo'])->name('seo.save');
                Route::post('publish', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'publish'])->name('publish');
                Route::post('versions/{version}/restore', [\Modules\Admin\App\Http\Controllers\AboutPageContentController::class, 'restoreVersion'])->name('versions.restore');
            });

            // Website Manager -> Contact Page Contents
            Route::prefix('website-manager/contact')->name('website-manager.contact.')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'index'])->name('index');
                Route::post('sections', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'saveSections'])->name('sections.save');
                Route::post('hero', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'saveHero'])->name('hero.save');
                Route::post('info', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'saveContactInfo'])->name('info.save');
                Route::post('form-config', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'saveFormConfig'])->name('form.save');
                Route::post('business-hours', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'saveBusinessHours'])->name('hours.save');
                Route::post('map-social', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'saveMapSocial'])->name('map-social.save');
                Route::post('seo', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'saveSeo'])->name('seo.save');
                Route::post('publish', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'publish'])->name('publish');
                Route::post('versions/{version}/restore', [\Modules\Admin\App\Http\Controllers\ContactPageContentController::class, 'restoreVersion'])->name('versions.restore');
            });

            // Website Manager -> Contact Enquiries
            Route::prefix('website-manager/enquiries')->name('website-manager.enquiries.')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\ContactEnquiryController::class, 'index'])->name('index');
                Route::get('{enquiry}', [\Modules\Admin\App\Http\Controllers\ContactEnquiryController::class, 'show'])->name('show');
                Route::post('{enquiry}/status', [\Modules\Admin\App\Http\Controllers\ContactEnquiryController::class, 'updateStatus'])->name('status');
            });

            // Website Manager -> Preloader Settings
            Route::prefix('website-manager/preloader')->name('website-manager.preloader.')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\PreloaderSettingController::class, 'index'])->name('index');
                Route::post('save', [\Modules\Admin\App\Http\Controllers\PreloaderSettingController::class, 'saveDraft'])->name('save');
                Route::post('publish', [\Modules\Admin\App\Http\Controllers\PreloaderSettingController::class, 'publish'])->name('publish');
                Route::post('versions/{version}/restore', [\Modules\Admin\App\Http\Controllers\PreloaderSettingController::class, 'restoreVersion'])->name('versions.restore');
            });

            // Website Manager -> Customer Login OTP Settings
            Route::prefix('website-manager/customer-login-otp')->name('website-manager.customer-login-otp.')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\CustomerLoginOtpSettingController::class, 'index'])->name('index');
                Route::post('save', [\Modules\Admin\App\Http\Controllers\CustomerLoginOtpSettingController::class, 'update'])->name('save');
            });
        });

        // Services: Units & Categories Management
        Route::prefix('services')->name('services.')->group(function () {
            // Units
            Route::middleware('permission:view-units,admin')->group(function () {
                Route::get('units', [\Modules\Admin\Http\Controllers\UnitController::class, 'index'])->name('units.index');
                Route::get('units/data', [\Modules\Admin\Http\Controllers\UnitController::class, 'data'])->name('units.data');
            });
            Route::post('units', [\Modules\Admin\Http\Controllers\UnitController::class, 'store'])->middleware('permission:create-units,admin')->name('units.store');
            Route::match(['put', 'patch'], 'units/{unit}', [\Modules\Admin\Http\Controllers\UnitController::class, 'update'])->middleware('permission:edit-units,admin')->name('units.update');
            Route::patch('units/{unit}/toggle-status', [\Modules\Admin\Http\Controllers\UnitController::class, 'toggleStatus'])->middleware('permission:edit-units,admin')->name('units.toggle-status');
            Route::delete('units/{unit}', [\Modules\Admin\Http\Controllers\UnitController::class, 'destroy'])->middleware('permission:delete-units,admin')->name('units.destroy');

            // Item Categories
            Route::middleware('permission:view-item-categories,admin')->group(function () {
                Route::get('categories', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'index'])->name('categories.index');
                Route::get('categories/data', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'data'])->name('categories.data');
                Route::get('categories/check-slug', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'checkSlug'])->name('categories.check-slug');
            });
            Route::post('categories', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'store'])->middleware('permission:create-item-categories,admin')->name('categories.store');
            Route::match(['put', 'patch'], 'categories/{itemCategory}', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'update'])->middleware('permission:edit-item-categories,admin')->name('categories.update');
            Route::patch('categories/{itemCategory}/toggle-status', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'toggleStatus'])->middleware('permission:edit-item-categories,admin')->name('categories.toggle-status');
            Route::delete('categories/{itemCategory}', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'destroy'])->middleware('permission:delete-item-categories,admin')->name('categories.destroy');

            // Laundry Items
            Route::middleware('permission:view-items,admin')->group(function () {
                Route::get('items', [\Modules\Admin\Http\Controllers\ItemController::class, 'index'])->name('items.index');
                Route::get('items/data', [\Modules\Admin\Http\Controllers\ItemController::class, 'data'])->name('items.data');
                Route::get('items/check-slug', [\Modules\Admin\Http\Controllers\ItemController::class, 'checkSlug'])->name('items.check-slug');
            });
            Route::post('items', [\Modules\Admin\Http\Controllers\ItemController::class, 'store'])->middleware('permission:create-items,admin')->name('items.store');
            Route::match(['put', 'patch'], 'items/{item}', [\Modules\Admin\Http\Controllers\ItemController::class, 'update'])->middleware('permission:edit-items,admin')->name('items.update');
            Route::patch('items/{item}/toggle-status', [\Modules\Admin\Http\Controllers\ItemController::class, 'toggleStatus'])->middleware('permission:edit-items,admin')->name('items.toggle-status');
            Route::delete('items/{item}', [\Modules\Admin\Http\Controllers\ItemController::class, 'destroy'])->middleware('permission:delete-items,admin')->name('items.destroy');

            // Laundry Services
            Route::middleware('permission:view-services,admin')->group(function () {
                Route::get('services', [\Modules\Admin\Http\Controllers\ServiceController::class, 'index'])->name('services.index');
                Route::get('services/data', [\Modules\Admin\Http\Controllers\ServiceController::class, 'data'])->name('services.data');
                Route::get('services/check-slug', [\Modules\Admin\Http\Controllers\ServiceController::class, 'checkSlug'])->name('services.check-slug');
                Route::get('services/items-by-category', [\Modules\Admin\Http\Controllers\ServiceController::class, 'getItemsByCategory'])->name('services.items-by-category');
            });
            Route::post('services', [\Modules\Admin\Http\Controllers\ServiceController::class, 'store'])->middleware('permission:create-services,admin')->name('services.store');
            Route::match(['put', 'patch'], 'services/{service}', [\Modules\Admin\Http\Controllers\ServiceController::class, 'update'])->middleware('permission:edit-services,admin')->name('services.update');
            Route::patch('services/{service}/toggle-status', [\Modules\Admin\Http\Controllers\ServiceController::class, 'toggleStatus'])->middleware('permission:edit-services,admin')->name('services.toggle-status');
            Route::delete('services/{service}', [\Modules\Admin\Http\Controllers\ServiceController::class, 'destroy'])->middleware('permission:delete-services,admin')->name('services.destroy');
        });

        // Store Section: Stores & Vendors Management
        Route::prefix('stores')->name('stores.')->group(function () {
            // Bangladesh Location API helpers for dynamic linked dropdowns
            Route::get('geo/districts', [\Modules\Admin\Http\Controllers\StoreController::class, 'getDistricts'])->name('geo.districts');
            Route::get('geo/upazilas', [\Modules\Admin\Http\Controllers\StoreController::class, 'getUpazilas'])->name('geo.upazilas');
            Route::get('geo/local-areas', [\Modules\Admin\Http\Controllers\StoreController::class, 'getLocalAreas'])->name('geo.local-areas');

            // Stores Management
            Route::get('uk-postcodes', [\Modules\Admin\Http\Controllers\StoreController::class, 'ukPostcodes'])
                ->middleware('permission:view-stores|create-stores|edit-stores,admin')
                ->name('uk-postcodes');

            Route::middleware('permission:view-stores,admin')->group(function () {
                Route::get('/', [\Modules\Admin\Http\Controllers\StoreController::class, 'index'])->name('index');
                Route::get('data', [\Modules\Admin\Http\Controllers\StoreController::class, 'data'])->name('data');
                Route::get('check-slug', [\Modules\Admin\Http\Controllers\StoreController::class, 'checkSlug'])->name('check-slug');
            });
            Route::post('/', [\Modules\Admin\Http\Controllers\StoreController::class, 'store'])->middleware('permission:create-stores,admin')->name('store');
            Route::match(['put', 'patch'], '{store}', [\Modules\Admin\Http\Controllers\StoreController::class, 'update'])->middleware('permission:edit-stores,admin')->name('update');
            Route::patch('{store}/toggle-orders', [\Modules\Admin\Http\Controllers\StoreController::class, 'toggleAcceptingOrders'])->middleware('permission:edit-stores,admin')->name('toggle-orders');
            Route::patch('{store}/toggle-status', [\Modules\Admin\Http\Controllers\StoreController::class, 'toggleStatus'])->middleware('permission:edit-stores,admin')->name('toggle-status');
            Route::delete('{store}', [\Modules\Admin\Http\Controllers\StoreController::class, 'destroy'])->middleware('permission:delete-stores,admin')->name('destroy');

            // Store Portal Users Management
            Route::prefix('{store}/users')->name('users.')->middleware('permission:edit-stores,admin')->group(function () {
                Route::get('/', [\Modules\Admin\App\Http\Controllers\StoreUserManagementController::class, 'index'])->name('index');
                Route::post('/', [\Modules\Admin\App\Http\Controllers\StoreUserManagementController::class, 'store'])->name('store');
                Route::patch('/{storeUser}/toggle', [\Modules\Admin\App\Http\Controllers\StoreUserManagementController::class, 'toggle'])->name('toggle');
                Route::post('/{storeUser}/reset-password', [\Modules\Admin\App\Http\Controllers\StoreUserManagementController::class, 'resetPassword'])->name('reset-password');
            });

            // Store Account & Ledger
            Route::get('{store}/account', [\Modules\Admin\App\Http\Controllers\StoreAccountController::class, 'show'])
                ->middleware('permission:view-store-accounts|view-stores,admin')
                ->name('account.show');
            Route::post('{store}/account/adjust', [\Modules\Admin\App\Http\Controllers\StoreAccountController::class, 'adjust'])
                ->middleware('permission:adjust-store-accounts,admin')
                ->name('account.adjust');
            Route::post('{store}/account/transactions/{transaction}/reverse', [\Modules\Admin\App\Http\Controllers\StoreAccountController::class, 'reverse'])
                ->middleware('permission:adjust-store-accounts,admin')
                ->name('account.reverse');

            // Vendors Management
            Route::prefix('vendors')->name('vendors.')->group(function () {
                Route::middleware('permission:manage-vendors,admin')->group(function () {
                    Route::get('/', [\Modules\Admin\Http\Controllers\VendorController::class, 'index'])->name('index');
                    Route::get('data', [\Modules\Admin\Http\Controllers\VendorController::class, 'data'])->name('data');
                    Route::post('/', [\Modules\Admin\Http\Controllers\VendorController::class, 'store'])->name('store');
                    Route::match(['put', 'patch'], '{vendor}', [\Modules\Admin\Http\Controllers\VendorController::class, 'update'])->name('update');
                    Route::patch('{vendor}/toggle-status', [\Modules\Admin\Http\Controllers\VendorController::class, 'toggleStatus'])->name('toggle-status');
                    Route::delete('{vendor}', [\Modules\Admin\Http\Controllers\VendorController::class, 'destroy'])->name('destroy');
                });
            });
        });

        // Delivery Persons Management
        Route::prefix('delivery-persons')->name('delivery-persons.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'index'])
                ->middleware('permission:view-delivery-persons,admin')->name('index');
            Route::get('create', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'create'])
                ->middleware('permission:create-delivery-persons,admin')->name('create');
            Route::post('/', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'store'])
                ->middleware('permission:create-delivery-persons,admin')->name('store');
            Route::get('{deliveryPerson}', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'show'])
                ->middleware('permission:view-delivery-persons,admin')->name('show');
            Route::get('{deliveryPerson}/edit', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'edit'])
                ->middleware('permission:edit-delivery-persons,admin')->name('edit');
            Route::match(['put', 'patch'], '{deliveryPerson}', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'update'])
                ->middleware('permission:edit-delivery-persons,admin')->name('update');
            Route::patch('{deliveryPerson}/toggle-status', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'toggleStatus'])
                ->middleware('permission:edit-delivery-persons,admin')->name('toggle-status');
            Route::delete('{deliveryPerson}', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'destroy'])
                ->middleware('permission:delete-delivery-persons,admin')->name('destroy');
            Route::get('{deliveryPerson}/documents/{document}/{side?}', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'downloadDocument'])
                ->middleware('permission:view-delivery-documents,admin')->name('documents.download');
            Route::patch('{deliveryPerson}/documents/{document}/verify', [\Modules\Admin\App\Http\Controllers\DeliveryPersonController::class, 'verifyDocument'])
                ->middleware('permission:view-delivery-documents,admin')->name('documents.verify');
        });

        // Delivery Assignments & Activity
        Route::prefix('delivery-assignments')->name('delivery-assignments.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\DeliveryAssignmentController::class, 'index'])
                ->middleware('permission:manage-delivery-assignments|view-delivery-persons,admin')->name('index');
            Route::get('eligible-persons', [\Modules\Admin\App\Http\Controllers\DeliveryAssignmentController::class, 'eligiblePersons'])
                ->middleware('permission:manage-delivery-assignments|view-delivery-persons,admin')->name('eligible-persons');
            Route::post('/', [\Modules\Admin\App\Http\Controllers\DeliveryAssignmentController::class, 'store'])
                ->middleware('permission:manage-delivery-assignments,admin')->name('store');
            Route::patch('{assignment}/transition', [\Modules\Admin\App\Http\Controllers\DeliveryAssignmentController::class, 'transition'])
                ->middleware('permission:manage-delivery-assignments,admin')->name('transition');
            Route::patch('{assignment}/cod', [\Modules\Admin\App\Http\Controllers\DeliveryAssignmentController::class, 'recordCod'])
                ->middleware('permission:manage-delivery-assignments,admin')->name('cod');
        });

        Route::get('delivery-activity', [\Modules\Admin\App\Http\Controllers\DeliveryAssignmentController::class, 'activity'])
            ->middleware('permission:view-delivery-persons|manage-delivery-assignments,admin')
            ->name('delivery-activity.index');

        // Store Withdrawal Requests (Manual Review & Settlement)
        Route::prefix('store-withdrawals')->name('store-withdrawals.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\StoreWithdrawalController::class, 'index'])
                ->middleware('permission:view-store-accounts|view-stores|manage-delivery-payouts,admin')->name('index');
            Route::get('{withdrawal}', [\Modules\Admin\App\Http\Controllers\StoreWithdrawalController::class, 'show'])
                ->middleware('permission:view-store-accounts|view-stores|manage-delivery-payouts,admin')->name('show');
            Route::post('{withdrawal}/approve', [\Modules\Admin\App\Http\Controllers\StoreWithdrawalController::class, 'approve'])
                ->middleware('permission:adjust-store-accounts|manage-delivery-payouts,admin')->name('approve');
            Route::post('{withdrawal}/reject', [\Modules\Admin\App\Http\Controllers\StoreWithdrawalController::class, 'reject'])
                ->middleware('permission:adjust-store-accounts|manage-delivery-payouts,admin')->name('reject');
            Route::post('{withdrawal}/settle', [\Modules\Admin\App\Http\Controllers\StoreWithdrawalController::class, 'settle'])
                ->middleware('permission:adjust-store-accounts|manage-delivery-payouts,admin')->name('settle');
        });

        // Delivery Account Balances & Payouts
        Route::prefix('delivery-payouts')->name('delivery-payouts.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\DeliveryPayoutController::class, 'index'])
                ->middleware('permission:manage-delivery-payouts|view-delivery-persons,admin')->name('index');
            Route::post('/', [\Modules\Admin\App\Http\Controllers\DeliveryPayoutController::class, 'store'])
                ->middleware('permission:manage-delivery-payouts,admin')->name('store');
            Route::post('adjustments', [\Modules\Admin\App\Http\Controllers\DeliveryPayoutController::class, 'storeAdjustment'])
                ->middleware('permission:manage-delivery-payouts,admin')->name('adjustments.store');
        });

        // Delivery Person Bank Withdrawal Requests (Manual Review & Settlement)
        Route::prefix('delivery-person-withdrawals')->name('delivery-person-withdrawals.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\DeliveryWithdrawalController::class, 'index'])
                ->middleware('permission:manage-delivery-payouts|view-delivery-persons,admin')->name('index');
            Route::get('{withdrawal}', [\Modules\Admin\App\Http\Controllers\DeliveryWithdrawalController::class, 'show'])
                ->middleware('permission:manage-delivery-payouts|view-delivery-persons,admin')->name('show');
            Route::post('{withdrawal}/approve', [\Modules\Admin\App\Http\Controllers\DeliveryWithdrawalController::class, 'approve'])
                ->middleware('permission:manage-delivery-payouts,admin')->name('approve');
            Route::post('{withdrawal}/reject', [\Modules\Admin\App\Http\Controllers\DeliveryWithdrawalController::class, 'reject'])
                ->middleware('permission:manage-delivery-payouts,admin')->name('reject');
            Route::post('{withdrawal}/settle', [\Modules\Admin\App\Http\Controllers\DeliveryWithdrawalController::class, 'settle'])
                ->middleware('permission:manage-delivery-payouts,admin')->name('settle');
        });

        // UK Address Management
        Route::prefix('uk-addresses')->name('uk-addresses.')->group(function () {
            Route::middleware('permission:view-uk-addresses,admin')->group(function () {
                Route::get('/', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'index'])->name('index');
                Route::get('data', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'data'])->name('data');
            });
            Route::post('/', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'store'])->middleware('permission:create-uk-addresses,admin')->name('store');
            Route::match(['put', 'patch'], '{ukAddress}', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'update'])->middleware('permission:edit-uk-addresses,admin')->name('update');
            Route::patch('{ukAddress}/toggle-status', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'toggleStatus'])->middleware('permission:edit-uk-addresses,admin')->name('toggle-status');
            Route::delete('{ukAddress}', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'destroy'])->middleware('permission:delete-uk-addresses,admin')->name('destroy');
            Route::get('export', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'export'])->middleware('permission:export-uk-addresses,admin')->name('export');
            Route::post('import/preview', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'previewCsv'])->middleware('permission:import-uk-addresses,admin')->name('import.preview');
            Route::post('import/process', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'importCsv'])->middleware('permission:import-uk-addresses,admin')->name('import.process');
        });

        // Hotel Management
        Route::prefix('hotels')->name('hotels.')->group(function () {
            Route::middleware('permission:view-hotels,admin')->group(function () {
                Route::get('/', [\Modules\Admin\Http\Controllers\HotelController::class, 'index'])->name('index');
                Route::get('data', [\Modules\Admin\Http\Controllers\HotelController::class, 'data'])->name('data');
            });
            Route::post('/', [\Modules\Admin\Http\Controllers\HotelController::class, 'store'])->middleware('permission:create-hotels,admin')->name('store');
            Route::match(['put', 'patch'], '{hotel}', [\Modules\Admin\Http\Controllers\HotelController::class, 'update'])->middleware('permission:edit-hotels,admin')->name('update');
            Route::patch('{hotel}/toggle-status', [\Modules\Admin\Http\Controllers\HotelController::class, 'toggleStatus'])->middleware('permission:edit-hotels,admin')->name('toggle-status');
            Route::delete('{hotel}', [\Modules\Admin\Http\Controllers\HotelController::class, 'destroy'])->middleware('permission:delete-hotels,admin')->name('destroy');
            Route::get('export', [\Modules\Admin\Http\Controllers\HotelController::class, 'export'])->middleware('permission:export-hotels,admin')->name('export');
            Route::post('import/preview', [\Modules\Admin\Http\Controllers\HotelController::class, 'previewCsv'])->middleware('permission:import-hotels,admin')->name('import.preview');
            Route::post('import/process', [\Modules\Admin\Http\Controllers\HotelController::class, 'importCsv'])->middleware('permission:import-hotels,admin')->name('import.process');
        });

        // Booking Schedule Management
        Route::prefix('booking-schedule')->name('booking-schedule.')->group(function () {
            Route::get('/', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'index'])
                ->middleware('permission:view-booking-schedule,admin')
                ->name('index');

            Route::middleware('permission:manage-booking-schedule,admin')->group(function () {
                Route::post('slots', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'storeSlot'])->name('slots.store');
                Route::put('slots/{slot}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'updateSlot'])->name('slots.update');
                Route::patch('slots/{slot}/toggle', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'toggleSlot'])->name('slots.toggle');
                Route::delete('slots/{slot}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'destroySlot'])->name('slots.destroy');

                Route::post('instructions', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'storeInstruction'])->name('instructions.store');
                Route::put('instructions/{instruction}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'updateInstruction'])->name('instructions.update');
                Route::patch('instructions/{instruction}/toggle', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'toggleInstruction'])->name('instructions.toggle');
                Route::delete('instructions/{instruction}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'destroyInstruction'])->name('instructions.destroy');

                Route::post('settings', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'updateSettings'])->name('settings.update');
                Route::post('blocked-dates', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'storeBlockedDate'])->name('blocked-dates.store');
                Route::delete('blocked-dates/{blockedDate}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'destroyBlockedDate'])->name('blocked-dates.destroy');
            });
        });

        // Booking Payments & Stripe Management
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'index'])
                ->middleware('permission:view-payments,admin')
                ->name('index');
            Route::post('{booking}/finalize', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'finalizeAndCharge'])
                ->middleware('permission:manage-payments,admin')
                ->name('finalize');
            Route::post('{booking}/retry', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'retryCharge'])
                ->middleware('permission:manage-payments,admin')
                ->name('retry');
            Route::post('{booking}/refund', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'refund'])
                ->middleware('permission:refund-payments,admin')
                ->name('refund');
        });

        // Dynamic Payment Gateway Management (Stripe, PayPal, COD)
        Route::prefix('payment-gateways')->name('payment-gateways.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'index'])
                ->middleware('permission:view-payment-gateways,admin')
                ->name('index');
            Route::post('/stripe', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'updateStripe'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('stripe.update');
            Route::post('/paypal', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'updatePayPal'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('paypal.update');
            Route::post('/cod', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'updateCod'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('cod.update');
            Route::post('/{gatewayCode}/toggle', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'toggleStatus'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('toggle');
            Route::post('/{gatewayCode}/test', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'testConnection'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('test');
            Route::delete('/{gatewayCode}/configuration', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'removeConfiguration'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('remove');
        });

        // Dynamic Booking Fee Settings (Service Fee & Collection & Delivery Fee)
        Route::prefix('booking-fees')->name('booking-fees.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\BookingFeeSettingController::class, 'index'])
                ->middleware('permission:view-booking-fees,admin')
                ->name('index');
            Route::post('/service-fee', [\Modules\Admin\App\Http\Controllers\BookingFeeSettingController::class, 'updateServiceFee'])
                ->middleware('permission:manage-booking-fees,admin')
                ->name('service-fee.update');
            Route::post('/delivery-fee', [\Modules\Admin\App\Http\Controllers\BookingFeeSettingController::class, 'updateDeliveryFee'])
                ->middleware('permission:manage-booking-fees,admin')
                ->name('delivery-fee.update');
        });

        // Global Country & Currency Settings
        Route::prefix('currency-settings')->name('currency-settings.')->middleware('permission:manage-settings,admin')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\CurrencySettingController::class, 'index'])->name('index');
            Route::post('/', [\Modules\Admin\App\Http\Controllers\CurrencySettingController::class, 'update'])->name('update');
        });

        // Advanced Admin Booking Management
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'index'])
                ->middleware('permission:view-bookings,admin')
                ->name('index');
            Route::get('/{booking}', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'show'])
                ->middleware('permission:view-bookings,admin')
                ->name('show');
            Route::get('/{booking}/edit', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'edit'])
                ->middleware('permission:manage-bookings,admin')
                ->name('edit');
            Route::get('/{booking}/categories/{category}/services', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'categoryServices'])
                ->middleware('permission:manage-bookings,admin')
                ->name('category-services');
            Route::put('/{booking}', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'update'])
                ->middleware('permission:manage-bookings,admin')
                ->name('update');
            Route::post('/{booking}/services', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'addService'])
                ->middleware('permission:manage-bookings,admin')
                ->name('services.add');
            Route::post('/{booking}/items', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'addItem'])
                ->middleware('permission:manage-bookings,admin')
                ->name('items.add');
            Route::delete('/{booking}/items/{item}', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'removeItem'])
                ->middleware('permission:manage-bookings,admin')
                ->name('items.remove');
            Route::post('/{booking}/status', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'updateStatus'])
                ->middleware('permission:manage-bookings,admin')
                ->name('status.update');
            Route::post('/{booking}/status/advance', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'advanceStatus'])
                ->middleware('permission:manage-bookings,admin')
                ->name('status.advance');
            Route::get('/{booking}/stores/search', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'searchStores'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('stores.search');
            Route::post('/{booking}/assign-store', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'assignStore'])
                ->middleware('permission:manage-bookings,admin')
                ->name('assign-store');
            Route::get('/{booking}/couriers/search', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'searchCouriers'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('couriers.search');
            Route::post('/{booking}/assign-courier', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'assignCourier'])
                ->middleware('permission:manage-bookings,admin')
                ->name('assign-courier');
            Route::get('/{booking}/invoice/preview', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'invoicePreview'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('invoice.preview');
            Route::get('/{booking}/invoice/print', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'invoicePrint'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('invoice.print');
            Route::post('/{booking}/cancel', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'cancel'])
                ->middleware('permission:cancel-bookings,admin')
                ->name('cancel');
        });

        // Admin Current Account & Ledger
        Route::prefix('current-account')->name('current-account.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\AdminAccountController::class, 'index'])
                ->middleware('permission:view-admin-accounts,admin')
                ->name('index');
            Route::get('/transactions', [\Modules\Admin\App\Http\Controllers\AdminAccountController::class, 'transactions'])
                ->middleware('permission:view-admin-accounts,admin')
                ->name('transactions');
            Route::get('/export', [\Modules\Admin\App\Http\Controllers\AdminAccountController::class, 'export'])
                ->middleware('permission:view-admin-accounts,admin')
                ->name('export');
            Route::post('/stripe-sync', [\Modules\Admin\App\Http\Controllers\AdminAccountController::class, 'syncStripeFees'])
                ->middleware('permission:view-admin-accounts,admin')
                ->name('stripe-sync');
            Route::get('/transactions/{uid}/detail', [\Modules\Admin\App\Http\Controllers\AdminAccountController::class, 'detail'])
                ->middleware('permission:view-admin-accounts,admin')
                ->name('detail');
            Route::post('/adjust', [\Modules\Admin\App\Http\Controllers\AdminAccountController::class, 'adjust'])
                ->middleware('permission:adjust-admin-accounts,admin')
                ->name('adjust');
            Route::post('/transactions/{transaction}/reverse', [\Modules\Admin\App\Http\Controllers\AdminAccountController::class, 'reverse'])
                ->middleware('permission:adjust-admin-accounts,admin')
                ->name('reverse');
        });

        // Dynamic Refund, Rewash & Return Policy + Case Management
        Route::prefix('refund-returns')->name('refund-returns.')->group(function () {
            Route::get('/cases', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'index'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('cases.index');
            Route::get('/cases/{claim}', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'show'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('cases.show');
            Route::post('/cases/{claim}/status', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'updateStatus'])
                ->middleware('permission:manage-bookings,admin')
                ->name('cases.status');
            Route::post('/cases/{claim}/refunds', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'approveAndCreateRefund'])
                ->middleware('permission:manage-bookings,admin')
                ->name('cases.refunds.store');
            Route::post('/cases/{claim}/rewash', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'scheduleRewash'])
                ->middleware('permission:manage-bookings,admin')
                ->name('cases.rewash');
            Route::post('/cases/{claim}/liability', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'applyLiability'])
                ->middleware('permission:manage-bookings,admin')
                ->name('cases.liability');

            Route::get('/refunds', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'refundsIndex'])
                ->middleware('permission:view-bookings|view-admin-accounts,admin')
                ->name('refunds.index');
            Route::post('/refunds/{refund}/execute', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'executeRefund'])
                ->middleware('permission:manage-bookings,admin')
                ->name('refunds.execute');
            Route::post('/refunds/{refund}/cod-paid', [\Modules\Admin\App\Http\Controllers\ClaimManagementController::class, 'markCodPaid'])
                ->middleware('permission:manage-bookings,admin')
                ->name('refunds.cod-paid');

            Route::get('/policies', [\Modules\Admin\App\Http\Controllers\RefundReturnPolicyController::class, 'index'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('policies.index');
            Route::get('/policies/history', [\Modules\Admin\App\Http\Controllers\RefundReturnPolicyController::class, 'history'])
                ->middleware('permission:view-bookings|manage-bookings,admin')
                ->name('policies.history');
            Route::post('/policies/draft', [\Modules\Admin\App\Http\Controllers\RefundReturnPolicyController::class, 'storeDraft'])
                ->middleware('permission:manage-bookings,admin')
                ->name('policies.draft');
            Route::post('/policies/versions/{version}/publish', [\Modules\Admin\App\Http\Controllers\RefundReturnPolicyController::class, 'publish'])
                ->middleware('permission:manage-bookings,admin')
                ->name('policies.publish');
        });

        // Delivery Person Cash Settlements, Online Transfer Verification & Portal Access
        Route::prefix('delivery-person-settlements')->name('delivery-person-settlements.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\DeliveryPersonSettlementController::class, 'index'])
                ->middleware('permission:view-delivery-settlements,admin')
                ->name('index');
            Route::post('/', [\Modules\Admin\App\Http\Controllers\DeliveryPersonSettlementController::class, 'store'])
                ->middleware('permission:manage-delivery-settlements,admin')
                ->name('store');
        });
        Route::post('booking-payments/{payment}/verify', [\Modules\Admin\App\Http\Controllers\DeliveryPersonSettlementController::class, 'verifyTransfer'])
            ->middleware('permission:manage-delivery-settlements,admin')
            ->name('booking-payments.verify');
        Route::post('delivery-persons/{deliveryPerson}/portal-access', [\Modules\Admin\App\Http\Controllers\DeliveryPersonSettlementController::class, 'updatePortalAccess'])
            ->middleware('permission:manage-delivery-settlements|manage-bookings,admin')
            ->name('delivery-persons.portal-access');

        // Database Backup & Restore
        Route::prefix('database-backups')->name('database-backups.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'index'])
                ->middleware('permission:view-database-backups,admin')
                ->name('index');
            Route::post('/', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'store'])
                ->middleware(['permission:create-database-backups,admin', 'throttle:10,1'])
                ->name('store');
            Route::post('/upload', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'upload'])
                ->middleware(['permission:create-database-backups|restore-database-backups,admin', 'throttle:10,1'])
                ->name('upload');
            Route::get('/{backup}/download', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'download'])
                ->middleware('permission:download-database-backups,admin')
                ->name('download');
            Route::post('/{backup}/verify', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'verify'])
                ->middleware('permission:view-database-backups|create-database-backups,admin')
                ->name('verify');
            Route::post('/{backup}/restore', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'restore'])
                ->middleware(['permission:restore-database-backups,admin', 'throttle:5,1'])
                ->name('restore');
            Route::get('/operations/{operation}/status', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'operationStatus'])
                ->middleware('permission:view-database-backups,admin')
                ->name('operations.status');
            Route::delete('/{backup}', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'destroy'])
                ->middleware('permission:delete-database-backups,admin')
                ->name('destroy');
            Route::post('/schedule', [\Modules\Admin\App\Http\Controllers\DatabaseBackupController::class, 'updateSchedule'])
                ->middleware('permission:manage-database-backup-schedules,admin')
                ->name('schedule.update');
        });

        // Database Cleanup (Module-Wise Safe Development Data Reset)
        Route::prefix('database-cleanup')->name('database-cleanup.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\DatabaseCleanupController::class, 'index'])
                ->middleware('permission:manage-database-cleanup,admin')
                ->name('index');
            Route::post('/preview', [\Modules\Admin\App\Http\Controllers\DatabaseCleanupController::class, 'preview'])
                ->middleware('permission:manage-database-cleanup,admin')
                ->name('preview');
            Route::post('/execute', [\Modules\Admin\App\Http\Controllers\DatabaseCleanupController::class, 'execute'])
                ->middleware(['permission:manage-database-cleanup,admin', 'throttle:5,1'])
                ->name('execute');
        });
    });
});

