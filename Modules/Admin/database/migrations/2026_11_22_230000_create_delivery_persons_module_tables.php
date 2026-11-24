<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_persons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('full_name', 190);
            $table->string('email', 190)->unique();
            $table->string('phone_country_code', 10)->default('+44');
            $table->string('phone', 30);
            $table->date('date_of_birth');
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 40)->nullable();
            $table->string('profile_photo', 255)->nullable();
            $table->text('internal_notes')->nullable();

            // Residential Address (separate from coverage postcodes)
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 120);
            $table->string('county_region', 120)->nullable();
            $table->string('country_code', 4)->default('GB');
            $table->string('country_name', 120)->default('United Kingdom');
            $table->string('residential_postcode', 20);
            $table->text('full_residential_address')->nullable();

            // Work Information
            $table->enum('employment_type', ['employee', 'contractor', 'other'])->default('contractor');
            $table->enum('work_status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('availability_status', ['available', 'busy', 'off_duty'])->default('available');
            $table->date('joining_date')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('vehicle_type', 40)->nullable();
            $table->string('vehicle_registration', 60)->nullable();
            $table->string('license_details', 150)->nullable();
            $table->text('work_notes')->nullable();

            // Per-Delivery Payment Configuration
            $table->enum('payment_calculation_type', ['fixed_per_assignment', 'separate_rates', 'admin_override'])->default('separate_rates');
            $table->unsignedBigInteger('per_collection_rate_minor')->default(0);
            $table->unsignedBigInteger('per_delivery_rate_minor')->default(0);
            $table->string('currency', 8)->default('GBP');
            $table->date('payment_effective_date')->nullable();
            $table->text('payment_notes')->nullable();

            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->string('archive_reason', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['work_status', 'availability_status']);
            $table->index('store_id');
        });

        Schema::create('delivery_person_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->enum('document_type', ['national_id', 'passport']);
            $table->text('document_number_encrypted');
            $table->string('document_number_masked', 60);
            $table->string('front_image_path', 255);
            $table->string('back_image_path', 255)->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->unsignedBigInteger('verified_by_admin_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['delivery_person_id', 'verification_status'], 'dp_docs_person_status_idx');
        });

        Schema::create('delivery_person_postcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->string('postcode', 16);
            $table->string('postcode_compact', 12);
            $table->timestamps();

            $table->unique(['delivery_person_id', 'postcode_compact'], 'dp_postcodes_unique');
            $table->index('postcode_compact');
        });

        Schema::create('delivery_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_reference', 40)->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->restrictOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->enum('assignment_type', ['customer_collection', 'final_delivery']);
            $table->enum('status', [
                'pending',
                'assigned',
                'accepted',
                'picked_up',
                'in_transit',
                'handed_over',
                'completed',
                'failed',
                'cancelled',
            ])->default('assigned');

            // Snapshots from booking & store
            $table->text('pickup_location');
            $table->string('pickup_postcode', 20)->nullable();
            $table->text('destination_location');
            $table->string('destination_postcode', 20)->nullable();
            $table->string('matched_postcode', 20)->nullable();
            $table->text('coverage_override_reason')->nullable();

            // Lifecycle timestamps
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Payment rate snapshot
            $table->unsignedBigInteger('rate_minor')->default(0);
            $table->string('currency', 8)->default('GBP');
            $table->string('rate_override_reason', 255)->nullable();
            $table->enum('earnings_status', ['pending', 'credited', 'reversed', 'not_payable'])->default('pending');

            // COD tracking (separate from delivery person earnings)
            $table->unsignedBigInteger('cod_expected_minor')->default(0);
            $table->unsignedBigInteger('cod_collected_minor')->default(0);
            $table->enum('cod_handover_status', ['not_applicable', 'pending_handover', 'handed_over', 'reconciled'])->default('not_applicable');
            $table->timestamp('cod_collected_at')->nullable();
            $table->timestamp('cod_handed_over_at')->nullable();
            $table->text('cod_notes')->nullable();

            $table->text('proof_of_delivery_notes')->nullable();
            $table->unsignedBigInteger('assigned_by_admin_id')->nullable();
            $table->unsignedBigInteger('completed_by_admin_id')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'assignment_type'], 'da_booking_type_idx');
            $table->index(['delivery_person_id', 'status'], 'da_person_status_idx');
            $table->index('assigned_at');
        });

        Schema::create('delivery_assignment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_assignment_id')->constrained('delivery_assignments')->cascadeOnDelete();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('event_type', 60);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('actor_admin_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['delivery_person_id', 'occurred_at'], 'dae_person_occurred_idx');
            $table->index(['booking_id', 'occurred_at'], 'dae_booking_occurred_idx');
        });

        Schema::create('delivery_person_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_reference', 50)->unique();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->restrictOnDelete();
            $table->foreignId('delivery_assignment_id')->nullable()->constrained('delivery_assignments')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->enum('transaction_type', [
                'assignment_earning',
                'earning_adjustment',
                'payout',
                'payout_reversal',
                'earning_reversal',
                'authorized_correction',
            ]);
            $table->enum('direction', ['credit', 'debit']);
            $table->bigInteger('amount_minor');
            $table->string('currency', 8)->default('GBP');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['delivery_person_id', 'transaction_type'], 'dpl_person_type_idx');
            $table->index(['delivery_assignment_id', 'transaction_type'], 'dpl_assign_type_idx');
        });

        Schema::create('delivery_person_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_reference', 50)->unique();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->restrictOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->constrained('delivery_person_ledger_entries')->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 8)->default('GBP');
            $table->enum('payment_method', ['bank_transfer', 'cash', 'other'])->default('bank_transfer');
            $table->string('external_reference', 120)->nullable();
            $table->date('payment_date');
            $table->enum('status', ['recorded', 'reversed'])->default('recorded');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->unsignedBigInteger('recorded_by_admin_id')->nullable();
            $table->timestamps();

            $table->index(['delivery_person_id', 'payment_date'], 'dpp_person_date_idx');
        });

        // Seed Delivery Persons permissions for admin guard
        $perms = [
            'view-delivery-persons',
            'create-delivery-persons',
            'edit-delivery-persons',
            'delete-delivery-persons',
            'manage-delivery-assignments',
            'manage-delivery-payouts',
            'view-delivery-documents',
        ];
        foreach ($perms as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'admin']);
        }

        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($perms);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_person_payouts');
        Schema::dropIfExists('delivery_person_ledger_entries');
        Schema::dropIfExists('delivery_assignment_events');
        Schema::dropIfExists('delivery_assignments');
        Schema::dropIfExists('delivery_person_postcodes');
        Schema::dropIfExists('delivery_person_documents');
        Schema::dropIfExists('delivery_persons');
    }
};
