<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('item_category_id')->nullable()->after('unit_id')->constrained('item_categories')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->after('item_category_id')->constrained('items')->nullOnDelete();
            $table->json('images')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['item_category_id']);
            $table->dropColumn(['item_id', 'item_category_id', 'images']);
        });
    }
};
