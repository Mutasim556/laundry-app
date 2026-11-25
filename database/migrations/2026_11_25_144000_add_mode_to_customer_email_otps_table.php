<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_email_otps', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_email_otps', 'mode')) {
                $table->string('mode', 20)->default('email')->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_email_otps', function (Blueprint $table) {
            if (Schema::hasColumn('customer_email_otps', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }
};
