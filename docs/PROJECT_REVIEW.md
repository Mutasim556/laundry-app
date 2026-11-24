# Project Review & Architecture Reference — FreshGo Laundry & Dry Cleaning

> **Generated:** 2026-11-24 (Read-only codebase, database, and test-suite audit on branch `branch1`)

---

## 1. Environment & Stack
- **Framework:** Laravel `13.39.0` (`nwidart/laravel-modules` `^13.3`, `spatie/laravel-permission` `^8.3`, `stripe/stripe-php` `^23.1`)
- **PHP:** `8.5.11` (requires `^8.3`)
- **Testing:** Pest `^4.7` (`pestphp/pest-plugin-laravel` `^4.1`) — **240 tests** (`237 passed`, `3 failed` as of 2026-11-24)
- **Frontend:** Laravel Blade + Bootstrap 5 + jQuery + Select2 (`public/admin/dist/libs/select2`) + custom CSS (`public/css/laundry-home.css`, `public/css/booking-address.css`)
- **Local Office Environment (XAMPP):**
  - Git branch: `branch1`
  - Path: `E:\xampp\htdocs\laundry-app`
  - Apache `Alias /laundry-app "E:/xampp/htdocs/laundry-app/public"` in `E:\xampp\apache\conf\httpd.conf` + `RewriteBase /laundry-app/` in `public/.htaccess`
  - URL: `http://localhost/laundry-app/`
  - Database: MySQL (`laundry_app`, `67` tables)

---

## 2. Module & Controller Map

### Customer & Public Web (`routes/web.php`)
- **Booking Flow (`BookingAddressController`):** 5-step session-backed booking (`Select Services` → `Address` → `Schedule` → `Contact` → `Review & Confirm`), UK postcode lookup, hotel search, dynamic `BookingFeeSetting` & `CurrencyService`.
- **Payments:** `StripePaymentController` (SetupIntent / off-session PaymentIntent + webhooks), `PayPalPaymentController` (order/capture), and Cash on Delivery (COD).
- **Customer Account & My Orders:** `CustomerAuthController` (email OTP), `CustomerAccountController` (profile, address book with contact details), `CustomerBookingController` (read-only `my-orders` + `orders.show`, `PUT/DELETE` blocked with `403`).

### Admin Panel (`Modules/Admin/routes/web.php`)
- **Booking Management (`BookingManagementController`):**
  - Search/filter index, redesigned 3-row Booking Details (`show`), Manage Booking (`edit` with single-select Select2 category groups, duplicate prevention, category removal/restoration, optimistic `version` locking, `BookingAuditLog`).
  - 11-stage sequential workflow (`processing` → `store_assigned` → `delivery_man_assigned` → `finalized` → `delivery_man_picked_up` → `store_received` → `store_processing` → `store_complete` → `delivery_man_assigned_for_store_pickup` → `on_delivery` → `completed`, plus terminal `cancelled`).
  - Inline Store Assignment (`searchStores`, `assignStore`), Collection & Final Delivery Person Assignment (`searchCouriers`, `assignCourier`), Invoice Preview/Print.
- **Stores & Vendors (`StoreController`, `VendorController`, `StoreUserManagementController`, `StoreAccountController`):**
  - Stores (`4` rows), Vendors (`3` rows), Store Portal Users, Store Accounts & Ledger.
- **Delivery Persons & Logistics (`DeliveryPersonController`, `DeliveryAssignmentController`, `DeliveryPersonSettlementController`, `DeliveryPayoutController`):**
  - Delivery Persons CRUD, postcode coverage (`delivery_person_postcodes`), private identity document storage on `local` disk (`delivery-persons/{id}/documents`), assignments (`DeliveryLogisticsService`), cash collection/settlements, payouts.
- **Settings & Catalog:** `ItemCategoryController`, `ItemController`, `ServiceController`, `UnitController`, `BookingFeeSettingController`, `CurrencySettingController`, `PaymentGatewayController`, `DatabaseBackupController`.

### Portals (`app/Http/Controllers/DeliveryPersonPortal`, `app/Http/Controllers/StorePortal`)
- `DeliveryPersonAuthController`, `DeliveryPersonBookingController`, `DeliveryPersonDashboardController`
- `StoreAuthController`, `StoreDashboardController`

---

## 3. Core Models & Financial Services
- **`App\Models\Booking`:** Preserves immutable checkout snapshots (`services_snapshot`, `address_snapshot`, `schedule_snapshot`, `contact_snapshot`, `fee_snapshot`, `payment_snapshot`). Operational adjustments live in `booking_operational_items`, `removed_operational_categories`, and `completed_operational_stages`.
- **Centralized Pricing:** `Booking::getOperationalPricingSummary(CurrencyService)` and `Booking::recalculateOperationalTotals(CurrencyService)` compute minor-unit totals (`Items Subtotal + Service Fee + Collection & Delivery Fee + Tip + Tax - Discount`) and keep `Payment Status` independent of `Pricing Status`.
- **Profit & Settlement Services:** `BookingPricingService`, `BookingFinancialSettlementService`, `BookingProfitService`, `StoreAccountService`, `StoreEarningsService`, `DeliveryPersonCashService`, `AdminAccountService`.

---

## 4. Known Issues & Priorities (Confirmed as of 2026-11-24)

### HIGH
1. **3 Failing Automated Tests:**
   - `tests/Feature/AdminBookingManagementTest.php` (`lines 1070, 1167`): Fails with `"Assign a store to this booking before completing Store Assigned."` because `BookingManagementController::advanceStatus()` now enforces store + collection/final delivery person assignment prerequisites before advancing past `store_assigned`, `delivery_man_assigned`, and `delivery_man_assigned_for_store_pickup`, while the earlier tests did not assign a store/delivery person.
   - `Modules/Admin/tests/Feature/AdminDeliveryPersonTest.php` (`line 298`): Fails (`0` vs `500`) because `DeliveryLogisticsService::createAssignment()` (lines 238–249) calculates `rate_minor` from a 50/50 split of `booking.collection_delivery_fee_minor` instead of falling back to `DeliveryPerson::per_collection_rate_minor` / `per_delivery_rate_minor` when booking delivery fee is `0` or when rate policy dictates person-level rates.

### MEDIUM
2. **Terminology Violation ("Courier" in Visible UI):**
   - `Modules/Admin/resources/views/bookings/show.blade.php` (`line 1402`) displays `"Allocated to Couriers (50/50):"` in the visible UI instead of `"Delivery Persons"`. Internal modal/route names (`assignCourierModal`, `assign-courier`) also still use `courier` internally.
3. **Route Ordering Safety (`stores/vendors` vs `{store}`):**
   - `Modules/Admin/routes/web.php` defines `Route::prefix('vendors')` (line 156) *after* `{store}` wildcard routes (lines 131–153). Moving `vendors` above `{store}` prevents future `GET {store}` routes from shadowing `/admin/stores/vendors`.
4. **Empty Operational Logistics Tables in Local DB:**
   - `delivery_persons`, `delivery_assignments`, `store_accounts`, `store_users`, and `booking_store_assignments` currently have `0` rows in `laundry_app`, so assignment & portal flows should be seeded/tested carefully before live use.

---

## 5. Safe Development Rules
- Never overwrite `.env` between Office (`XAMPP`, `branch1`, `http://localhost/laundry-app/`) and Home (`Laragon`, `branch-home`, `http://laundry-app.test`).
- Never run `migrate:fresh`, truncate tables, or regenerate `APP_KEY`.
- Never mutate `bookings.*_snapshot` columns from admin operational edits; use `booking_operational_items`, `removed_operational_categories`, and `BookingAuditLog`.
- Remember `Eloquent\Model` has a `protected array $changes = []` property — always read `BookingAuditLog` via `$log->getAttribute('changes')` inside `Model` subclasses.
