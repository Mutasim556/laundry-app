<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_status', 64)->default('confirmed')->change();
        });
    }

    public function down(): void
    {
        // Keep varchar(64) safely on rollback to avoid truncating longer status values
    }
};
