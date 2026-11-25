# Complete Project Review & Architecture Reference — FreshGo Laundry & Dry Cleaning

> **Audit Date:** 2026-11-24  
> **Mode:** Strict read-only codebase, database schema, route, and test-suite audit  
> **Active Environment:** Home / Laragon (`http://laundry-app.test`, Git branch `branch-home`)

---

## 1. Environment & Technology Stack
- **PHP:** `8.5.12` (`composer.json` requires `^8.3`)
- **Framework:** Laravel `13.39.0` (`laravel/framework: ^13.17`)
- **Key PHP Packages (`composer.json`):**
  - `nwidart/laravel-modules: ^13.3` (Admin module under `Modules/Admin/`)
  - `spatie/laravel-permission: ^8.3` (RBAC permissions on `admin` guard)
  - `stripe/stripe-php: ^23.1` (Stripe SetupIntent, PaymentIntent, Checkout, BalanceTransaction fee reconciliation, Refunds, Webhooks)
  - `laravel/boost: ^2.10` (dev)
  - `pestphp/pest: ^4.7` (test runner)
- **Frontend & Build (`package.json`):**
  - `vite: ^8.0.0`, `laravel-vite-plugin: ^3.1`, `tailwindcss: ^4.0.0`, `@tailwindcss/vite: ^4.0.0`, `toastr: ^2.1.4`
  - **Portals & Admin UI:** Laravel Blade + Bootstrap 5 + jQuery + Select2 + Material Design Icons (`mdi`)
- **Runtime Configuration (`.env` safe keys):**
  - `APP_ENV=local`
  - `APP_URL=http://laundry-app.test`
  - `DB_CONNECTION=mysql`, `DB_DATABASE=laundry_app`
  - `QUEUE_CONNECTION=database`
  - `MAIL_MAILER=smtp`

---

## 2. Authentication & Guard Architecture (`config/auth.php`)
Four independent session guards and providers isolate each portal:
1. **`web` (`users`)** — Customers (email OTP login, `/my-orders`, `/profile`, `/address-book`, `/saved-payment-methods`, claims).
2. **`admin` (`admins`)** — Admin Panel (`Modules/Admin/`), protected by `spatie/laravel-permission` middleware (`permission:...,admin`).
3. **`store` (`store_users`)** — Store Portal (`routes/store.php`), scoped to `store_user->store_id`.
4. **`delivery_person` (`delivery_persons`)** — Delivery Person Portal (`routes/delivery_person.php`), scoped to `delivery_person->id`.

---

## 3. Database Schema Overview (`laundry_app` — 83 Tables)
Verified via `Schema::getTableListing()`:
- **Core Booking & Catalog:** `bookings`, `booking_sequences`, `booking_operational_categories`, `booking_operational_items`, `booking_audit_logs`, `booking_invoices`, `booking_driver_instructions`, `booking_collection_otps`, `services`, `item_categories`, `items`, `units`, `booking_availability_settings`, `booking_blocked_dates`, `booking_time_slots`, `booking_time_slot_weekdays`, `booking_fee_settings`, `booking_fee_audit_logs`.
- **Customer & Addressing:** `users`, `customer_addresses`, `customer_email_otps`, `customer_payment_methods`, `uk_addresses`, `hotels`, `divisions`, `districts`, `upazilas`, `local_areas`.
- **Payments & Gateways:** `payment_gateways`, `payment_gateway_audit_logs`, `booking_customer_payments`, `stripe_webhook_events`, `currency_change_audit_logs`, `settings`.
- **Stores & Store Portal:** `vendors`, `stores`, `store_users`, `booking_store_assignments`, `booking_store_handovers`, `booking_store_pickup_requests`, `booking_store_earnings`, `store_accounts`, `store_transactions`, `store_bank_accounts`, `store_withdrawal_requests`.
- **Delivery Persons & Logistics:** `delivery_persons`, `delivery_person_postcodes`, `delivery_person_documents`, `delivery_assignments`, `delivery_assignment_events`, `delivery_person_ledger_entries`, `delivery_person_cash_transactions`, `delivery_person_settlements`, `delivery_person_payouts`, `delivery_person_bank_accounts`, `delivery_person_withdrawal_requests`.
- **Admin Financial Ledger:** `admin_accounts`, `admin_account_transactions`, `booking_financial_settlements`.
- **Refunds, Returns & Claims:** `refund_return_policies`, `refund_return_policy_versions`, `customer_service_claims`, `customer_service_claim_items`, `claim_evidence`, `claim_status_history`, `claim_delivery_jobs`, `refund_transactions`.
- **Platform & Auth/Infra:** `admins`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`, `database_backups`, `database_backup_operations`.

---

## 4. Module & Service Architecture

### 4.1 11-Stage Operational Workflow (`App\Models\Booking`)
Sequential stages (`Booking::OPERATIONAL_STATUSES`):
1. `processing`
2. `store_assigned` (requires active `BookingStoreAssignment`)
3. `delivery_man_assigned` (requires Collection `DeliveryAssignment`)
4. `finalized` (locks/recalculates operational items & store commission snapshot)
5. `delivery_man_picked_up`
6. `store_received` (triggered via Store Handover acceptance + credits Collection DP earnings)
7. `store_processing`
8. `store_complete`
9. `delivery_man_assigned_for_store_pickup` (requires Final Delivery `DeliveryAssignment` & Store Pickup Request)
10. `on_delivery`
11. `completed` (credits Final DP earnings, Store net earnings, and Admin settlement)
Plus terminal state: `cancelled`.

Operational progress is tracked in `bookings.completed_operational_stages` (JSON), separate from `payment_status`, and synchronized across Admin, Store (`StoreOperationalStatusService`), Delivery Person, and Customer `/my-orders` (`Booking::getCustomerStatusGroup()`).

### 4.2 Service Layer (`app/Services/` & `Modules/Admin/App/Services/`)
- **Pricing & Profit:** `BookingPricingService`, `BookingOperationalEditorService`, `BookingProfitService`, `CurrencyService`.
- **Settlements & Accounts:** `BookingFinancialSettlementService`, `AdminAccountService`, `CurrentAccountReportService`, `StoreAccountService`, `StoreEarningsService`.
- **Store Operations & Payouts:** `StoreOperationalStatusService`, `StoreHandoverService`, `StorePickupDeliveryService`, `StoreBankAccountService`, `StoreWithdrawalService`.
- **Delivery Operations & Payouts:** `DeliveryLogisticsService`, `DeliveryPersonAuthorizationService`, `DeliveryPersonCashService`, `DeliveryPersonBankAccountService`, `DeliveryPersonWithdrawalService`, `CollectionOtpService`, `CollectionPaymentService`.
- **Payments & Reconciliation:** `StripePaymentService`, `StripeFeeReconciliationService`, `PayPalPaymentService`.
- **Refunds, Returns & Claims:** `PolicyResolutionService`, `ClaimWorkflowService`, `RefundCalculationService`, `RefundExecutionService`.
- **Invoicing:** `BookingInvoiceService`.

---

## 5. Module Status Classification

| Module / Capability | Status | Evidence & Notes |
|---|---|---|
| Customer 5-Step Booking Flow & Checkout | **COMPLETE** | `routes/web.php`, `BookingAddressController`, `StripePaymentController`, `PayPalPaymentController`, COD. |
| Customer `/my-orders` & Guest Order Claim | **COMPLETE** | `CustomerBookingController`, `CustomerAccountController::claimGuestOrder`. |
| Admin Booking Management & 11-Stage Workflow | **PARTIALLY COMPLETE** | Core workflow works; 2 legacy tests in `AdminBookingManagementTest` fail due to missing store prerequisite setup. |
| Store Portal & Store Handover Workflow | **PARTIALLY COMPLETE** | `StoreDashboardController`, `StoreHandoverService`, `StoreOperationalStatusService`; 1 test in `StorePortalTest` fails on `GBP 147.00` visibility assertion. |
| Store Pickup Request & Final Delivery Flow | **PARTIALLY COMPLETE** | `StorePickupDeliveryService` & `routes/store.php` implemented; 1 test in `DeliveryPersonOnlinePaymentFlowTest` fails on `Final Amount: GBP 20.00` assertion. |
| Delivery Person Portal (Collection & Final) | **COMPLETE** | `routes/delivery_person.php`, `DeliveryPersonBookingController`, OTP, payment collection, handover request. |
| Delivery Person Logistics & Rate Calculation | **PARTIALLY COMPLETE** | `DeliveryLogisticsService`; 1 test in `AdminDeliveryPersonTest` fails (`rate_minor` `0` vs `500` fallback). |
| Stripe Payment & Fee Reconciliation | **COMPLETE** | `StripePaymentService`, `StripeFeeReconciliationService`, `AdminAccountController::syncStripeFees`. |
| PayPal & COD Payment Channels | **COMPLETE** | `PayPalPaymentService`, `CollectionPaymentService`, `PaymentGatewayController`. |
| Admin Current Account & Monthly Reporting | **COMPLETE** | `CurrentAccountReportService`, `AdminAccountService`, single-currency month/range aggregation. |
| Store & DP Bank Accounts & Withdrawals | **COMPLETE** | `StoreWithdrawalService`, `DeliveryPersonWithdrawalService` with `lockForUpdate()`, reservation ledger TXs, and `idempotency_key`. |
| Refunds, Returns, Rewash & Claims | **COMPLETE** | `PolicyResolutionService`, `ClaimWorkflowService`, `RefundCalculationService`, `RefundExecutionService`, `CustomerClaimController`, `ClaimManagementController`. |

---

## 6. Automated Test Suite Results (`php artisan test`)
- **Total:** `259` tests (`2379` assertions, `21.38s`)
- **Passed:** `254`
- **Failed:** `5`
  1. `tests/Feature/AdminBookingManagementTest.php:1070` — `"Assign a store to this booking before completing Store Assigned."`
  2. `tests/Feature/AdminBookingManagementTest.php:1167` — `"Assign a store to this booking before completing Store Assigned."`
  3. `tests/Feature/DeliveryPersonOnlinePaymentFlowTest.php:453` — Store Portal response unexpectedly contains `"Final Amount: GBP 20.00"`.
  4. `tests/Feature/StorePortalTest.php:231` — Store Portal response unexpectedly contains `"GBP 147.00"` (customer total leaked to Store view).
  5. `Modules/Admin/tests/Feature/AdminDeliveryPersonTest.php:298` — `DeliveryAssignment::rate_minor` is `0` instead of `500` when booking delivery fee is `0`.

---

## 7. Confirmed Bugs, Risks & Technical Debt
1. **Store Portal Financial Isolation Leak (`HIGH`):** `StorePortalTest:231` and `DeliveryPersonOnlinePaymentFlowTest:453` show that the Store Portal booking view currently renders customer-facing totals (`GBP 147.00` / `Final Amount: GBP 20.00`) that should be hidden from store users.
2. **Delivery Person Rate Fallback (`HIGH`):** `DeliveryLogisticsService::createAssignment()` computes `rate_minor` from `booking.collection_delivery_fee_minor` without falling back to `DeliveryPerson::per_collection_rate_minor` / `per_delivery_rate_minor` when the booking delivery fee is `0`.
3. **Outdated Workflow Tests (`MEDIUM`):** Two tests in `AdminBookingManagementTest.php` advance `store_assigned` without first assigning a store.
4. **Visible UI Terminology ("Courier") (`MEDIUM`):** `Modules/Admin/resources/views/bookings/show.blade.php:1450` displays `"Allocated to Couriers (50/50):"` instead of `"Delivery Persons"`. Internal modal/CSS/JS identifiers (`assignCourierModal`, `js-open-assign-courier`) also retain `courier`.
5. **Parallel Test Temp Collisions on Windows (`LOW`):** Running `php artisan test` concurrently in two processes causes Windows `Access is denied (code: 5)` on `storage/framework/views/*.tmp` and `storage/framework/testing/disks/`. Always run Pest in a single process.

---

## 8. Safe Development Rules
- Never switch Git branches (`branch-home` vs `branch1`), overwrite `.env`, or regenerate `APP_KEY`.
- Never run `migrate:fresh` or truncate tables on `laundry_app`.
- Never mutate `bookings.*_snapshot` columns during admin/DP edits; use `booking_operational_items` and `BookingAuditLog`.
- In `Eloquent\Model` subclasses, access `BookingAuditLog`'s `changes` column via `$log->getAttribute('changes')`.
