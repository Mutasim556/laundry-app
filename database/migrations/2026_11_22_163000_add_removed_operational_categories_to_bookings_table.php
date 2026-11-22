<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'removed_operational_categories')) {
                $table->json('removed_operational_categories')->nullable()->after('services_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'removed_operational_categories')) {
                $table->dropColumn('removed_operational_categories');
            }
        });
    }
};
