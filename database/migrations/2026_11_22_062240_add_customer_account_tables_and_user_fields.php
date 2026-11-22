<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();

                if (! Schema::hasColumn('users', 'country_code')) {
                    $table->string('country_code', 10)->nullable()->after('email_verified_at');
                }
                if (! Schema::hasColumn('users', 'phone')) {
                    $table->string('phone', 32)->nullable()->after('country_code');
                }
                if (! Schema::hasColumn('users', 'gender')) {
                    $table->string('gender', 32)->nullable()->after('phone');
                }
                if (! Schema::hasColumn('users', 'profile_completed_at')) {
                    $table->timestamp('profile_completed_at')->nullable()->after('gender');
                }
            });
        }

        Schema::create('customer_email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 160)->index();
            $table->string('otp_hash', 255);
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('resend_available_at')->nullable();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 80)->nullable();
            $table->string('address_type', 20)->default('home');
            $table->string('postcode', 20)->nullable();
            $table->string('formatted_address', 255)->nullable();
            $table->string('address_details', 255)->nullable();
            $table->string('hotel_name', 160)->nullable();
            $table->string('hotel_address', 255)->nullable();
            $table->string('room_number', 40)->nullable();
            $table->string('hotel_collection_type', 30)->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customer_email_otps');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $dropCols = [];
                foreach (['country_code', 'phone', 'gender', 'profile_completed_at'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }
    }
};
