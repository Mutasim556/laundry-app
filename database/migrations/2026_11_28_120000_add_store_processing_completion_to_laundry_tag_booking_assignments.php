<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundry_tag_booking_assignments', function (Blueprint $table) {
            $table->unsignedSmallInteger('bag_sequence')->nullable()->after('package_label');
            $table->boolean('is_manifest_finalized')->default(false)->after('active_lock');
            $table->timestamp('store_processing_completed_at')->nullable()->after('verification_notes');
            $table->foreignId('store_processing_completed_by_store_user_id')
                ->nullable()
                ->after('store_processing_completed_at')
                ->constrained('store_users', 'id', 'fk_ltba_proc_done_su')
                ->nullOnDelete();
            $table->string('store_processing_notes', 500)->nullable()->after('store_processing_completed_by_store_user_id');

            $table->index(['booking_id', 'active_lock', 'store_processing_completed_at'], 'idx_ltba_proc_progress');
        });
    }

    public function down(): void
    {
        Schema::table('laundry_tag_booking_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_ltba_proc_progress');
            $table->dropForeign('fk_ltba_proc_done_su');
            $table->dropColumn([
                'bag_sequence',
                'is_manifest_finalized',
                'store_processing_completed_at',
                'store_processing_completed_by_store_user_id',
                'store_processing_notes',
            ]);
        });
    }
};
