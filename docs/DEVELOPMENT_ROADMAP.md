# Development Roadmap — FreshGo Laundry & Dry Cleaning

> **Baseline:** 2026-11-24 (`branch-home`, 259 Pest tests: 254 passing, 5 failing)

---

## Priority 1 — Fix Failing Tests & Financial Visibility Leaks (Immediate)

### 1.1 Store Portal Pricing Visibility Isolation
- **Target:** Store Portal booking details view (`resources/views/store-portal/bookings/show.blade.php` & `StoreDashboardController`)
- **Issue:** `StorePortalTest:231` (`GBP 147.00`) and `DeliveryPersonOnlinePaymentFlowTest:453` (`Final Amount: GBP 20.00`) fail because customer-facing delivery/service fees or final customer totals appear in the Store Portal HTML.
- **Action:** Audit the Store Portal Blade view to ensure only store-eligible service subtotal, commission rate/amount, and net store earnings are rendered once `finalized` is complete.

### 1.2 Delivery Person Assignment Rate Fallback
- **Target:** `Modules/Admin/App/Services/DeliveryLogisticsService::createAssignment()`
- **Issue:** `Modules/Admin/tests/Feature/AdminDeliveryPersonTest.php:298` expects `rate_minor === 500` when `booking.collection_delivery_fee_minor` is `0`, using `DeliveryPerson::per_collection_rate_minor` / `per_delivery_rate_minor`.
- **Action:** Preserve 50/50 booking delivery fee split when configured, and fall back to the delivery person's configured per-collection / per-delivery rate when the booking fee split yields `0`.

### 1.3 Align Legacy Workflow Tests with Store Assignment Prerequisite
- **Target:** `tests/Feature/AdminBookingManagementTest.php` (lines `1070`, `1167`)
- **Issue:** Tests attempt to mark `store_assigned` Done without creating a `BookingStoreAssignment`.
- **Action:** Seed/assign an active store (and collection/final delivery persons where required) in those test cases before advancing through the 11 stages.

---

## Priority 2 — UI Consistency & Route Hygiene (Short-Term)

### 2.1 Remove Remaining Visible "Courier" Terminology
- **Target:** `Modules/Admin/resources/views/bookings/show.blade.php:1450`
- **Action:** Replace `"Allocated to Couriers (50/50):"` with `"Allocated to Delivery Persons (50/50):"`. Keep internal DOM IDs/routes unchanged unless refactored with full JS/test coverage.

### 2.2 Admin Route Ordering Audit (`stores/vendors` vs `{store}`)
- **Target:** `Modules/Admin/routes/web.php`
- **Action:** Ensure static sub-prefixes (`vendors`, `users`, `accounts`, `withdrawals`) are registered before wildcard `{store}` routes so future `GET {store}` endpoints cannot shadow them.

---

## Priority 3 — Operational Readiness & Hardening (Medium-Term)

### 3.1 Live Webhook & Queue Worker Verification
- Verify `QUEUE_CONNECTION=database` worker execution for queued emails (`BookingNotificationMail`) and asynchronous Stripe/PayPal webhook processing.
- Verify Stripe balance transaction fee reconciliation (`admin.current-account.stripe-sync`) against live/test Stripe API responses.

### 3.2 End-to-End Browser Verification Across All 4 Portals
- Run a seeded smoke test across Customer (`/book`, `/my-orders`), Admin (`/admin/bookings/{id}`, `/admin/current-account`, `/admin/refund-returns/cases`), Store (`/store/handovers`, `/store/bookings/{id}`), and Delivery Person (`/delivery-person/collections`, `/delivery-person/final-deliveries`).
