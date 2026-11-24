<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_person_ledger_entries', function (Blueprint $table) {
            $table->string('transaction_type', 50)->change();
        });
    }

    public function down(): void
    {
        // Keep widened varchar(50) safely on rollback to avoid truncating withdrawal ledger types.
    }
};
