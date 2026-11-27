<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('laundry_tag_events')) {
            $this->down();
        }

        Schema::create('tag_sequences', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('tag_printer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false)->index();
            $table->string('preset', 32)->default('a4'); // a4, letter, custom, roll
            $table->string('orientation', 16)->default('portrait'); // portrait, landscape
            $table->decimal('paper_width_mm', 7, 2)->default(210.00);
            $table->decimal('paper_height_mm', 7, 2)->default(297.00);
            $table->decimal('tag_width_mm', 7, 2)->default(40.00);
            $table->decimal('tag_height_mm', 7, 2)->default(125.00);
            $table->decimal('margin_top_mm', 7, 2)->default(10.00);
            $table->decimal('margin_bottom_mm', 7, 2)->default(10.00);
            $table->decimal('margin_left_mm', 7, 2)->default(10.00);
            $table->decimal('margin_right_mm', 7, 2)->default(10.00);
            $table->decimal('gap_horizontal_mm', 7, 2)->default(4.00);
            $table->decimal('gap_vertical_mm', 7, 2)->default(5.00);
            $table->unsignedTinyInteger('tags_per_row')->default(4);
            $table->unsignedTinyInteger('tags_per_column')->default(2);
            $table->decimal('qr_size_mm', 6, 2)->default(24.00);
            $table->decimal('logo_size_mm', 6, 2)->default(14.00);
            $table->decimal('font_size_pt', 5, 2)->default(7.50);
            $table->decimal('code_font_size_pt', 5, 2)->default(8.50);
            $table->boolean('show_brand_name')->default(true);
            $table->boolean('show_booking_field')->default(true);
            $table->boolean('show_package_field')->default(true);
            $table->boolean('show_cut_guides')->default(true);
            $table->boolean('show_hole_guide')->default(true);
            $table->boolean('background_printing')->default(true);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('laundry_tag_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_reference', 64)->unique();
            $table->string('label', 120)->nullable();
            $table->string('template', 40)->default('fresh_go_hanger_v1');
            $table->foreignId('printer_profile_id')->nullable()->constrained('tag_printer_profiles')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('code_from', 32)->nullable();
            $table->string('code_to', 32)->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('generated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('laundry_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tag_code', 32)->unique();
            $table->string('qr_signature', 32);
            $table->foreignId('batch_id')->constrained('laundry_tag_batches')->cascadeOnDelete();
            $table->string('status', 32)->default('available')->index();
            $table->foreignId('current_delivery_person_id')->nullable()->constrained('delivery_persons')->nullOnDelete();
            $table->foreignId('current_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('print_status', 32)->default('unprinted')->index();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('at_store_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamp('lost_or_damaged_at')->nullable();
            $table->string('status_reason', 500)->nullable();
            $table->boolean('is_reusable')->default(false);
            $table->timestamps();

            $table->index(['current_delivery_person_id', 'status']);
            $table->index(['current_booking_id', 'status']);
            $table->index(['batch_id', 'status']);
        });

        Schema::create('laundry_tag_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('allocation_reference', 64)->unique();
            $table->foreignId('delivery_person_id')->constrained('delivery_persons')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status', 32)->default('completed');
            $table->string('notes', 500)->nullable();
            $table->foreignId('allocated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamps();

            $table->index(['delivery_person_id', 'allocated_at']);
        });

        Schema::create('laundry_tag_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_id')->constrained('laundry_tag_allocations')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('laundry_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['allocation_id', 'tag_id']);
        });

        Schema::create('laundry_tag_print_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('print_reference', 64)->unique();
            $table->foreignId('batch_id')->nullable()->constrained('laundry_tag_batches')->nullOnDelete();
            $table->foreignId('printer_profile_id')->nullable()->constrained('tag_printer_profiles')->nullOnDelete();
            $table->json('tag_ids');
            $table->unsignedInteger('tag_count');
            $table->unsignedTinyInteger('copies')->default(1);
            $table->unsignedTinyInteger('start_offset')->default(0);
            $table->boolean('is_reprint')->default(false);
            $table->string('status', 32)->default('prepared')->index(); // prepared, requested, confirmed
            $table->foreignId('requested_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('laundry_tag_booking_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('laundry_tags', 'id', 'fk_ltba_tag')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings', 'id', 'fk_ltba_booking')->cascadeOnDelete();
            $table->foreignId('delivery_assignment_id')->nullable()->constrained('delivery_assignments', 'id', 'fk_ltba_deliv_asg')->nullOnDelete();
            $table->foreignId('handover_id')->nullable()->constrained('booking_store_handovers', 'id', 'fk_ltba_handover')->nullOnDelete();
            $table->foreignId('delivery_person_id')->nullable()->constrained('delivery_persons', 'id', 'fk_ltba_dp')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores', 'id', 'fk_ltba_store')->nullOnDelete();
            $table->string('package_label', 60)->nullable();
            $table->unsignedTinyInteger('active_lock')->nullable(); // 1 while active, NULL when released/completed
            $table->string('status', 32)->default('active')->index(); // active, store_verified, completed, unassigned, replaced, cancelled
            $table->string('assigned_method', 24)->default('manual'); // manual, qr, admin
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by_dp_id')->nullable()->constrained('delivery_persons', 'id', 'fk_ltba_assigned_dp')->nullOnDelete();
            $table->timestamp('store_verified_at')->nullable();
            $table->foreignId('verified_by_store_user_id')->nullable()->constrained('store_users', 'id', 'fk_ltba_verified_su')->nullOnDelete();
            $table->string('verification_method', 24)->nullable(); // manual, qr
            $table->string('verification_notes', 500)->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason', 500)->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamps();

            $table->unique(['tag_id', 'active_lock'], 'uniq_tag_active_lock');
            $table->index(['booking_id', 'status'], 'idx_ltba_booking_status');
            $table->index(['handover_id', 'status'], 'idx_ltba_handover_status');
        });

        Schema::create('laundry_tag_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('laundry_tags', 'id', 'fk_lte_tag')->cascadeOnDelete();
            $table->string('event_type', 64)->index();
            $table->string('previous_status', 32)->nullable();
            $table->string('new_status', 32)->nullable();
            $table->foreignId('booking_id')->nullable()->constrained('bookings', 'id', 'fk_lte_booking')->nullOnDelete();
            $table->foreignId('delivery_person_id')->nullable()->constrained('delivery_persons', 'id', 'fk_lte_dp')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores', 'id', 'fk_lte_store')->nullOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('laundry_tag_allocations', 'id', 'fk_lte_alloc')->nullOnDelete();
            $table->foreignId('print_job_id')->nullable()->constrained('laundry_tag_print_jobs', 'id', 'fk_lte_print_job')->nullOnDelete();
            $table->string('actor_type', 40)->nullable(); // admin, delivery_person, store_user, system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 120)->nullable();
            $table->string('reason', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['tag_id', 'occurred_at'], 'idx_lte_tag_occ');
            $table->index(['booking_id', 'occurred_at'], 'idx_lte_booking_occ');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laundry_tag_events');
        Schema::dropIfExists('laundry_tag_booking_assignments');
        Schema::dropIfExists('laundry_tag_print_jobs');
        Schema::dropIfExists('laundry_tag_allocation_items');
        Schema::dropIfExists('laundry_tag_allocations');
        Schema::dropIfExists('laundry_tags');
        Schema::dropIfExists('laundry_tag_batches');
        Schema::dropIfExists('tag_printer_profiles');
        Schema::dropIfExists('tag_sequences');
    }
};
