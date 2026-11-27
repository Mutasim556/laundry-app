<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_person_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('tracking_token_hash', 64)->nullable();
            $table->string('full_name', 150);
            $table->string('email', 150)->index();
            $table->string('phone_country_code', 10)->default('+44');
            $table->string('phone', 30);
            $table->string('phone_normalized', 30)->index();
            $table->date('date_of_birth')->nullable();

            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100);
            $table->string('postcode', 30);
            $table->string('country_code', 5)->default('GB');
            $table->string('country_name', 100)->default('United Kingdom');
            $table->string('service_area', 150)->nullable();

            $table->string('delivery_method', 30)->default('bicycle');
            $table->string('vehicle_type', 60)->nullable();
            $table->string('vehicle_registration', 40)->nullable();
            $table->string('driving_licence_number', 60)->nullable();
            $table->text('experience_notes')->nullable();

            $table->string('password');
            $table->boolean('consent_accepted')->default(true);

            $table->string('status', 40)->default('submitted')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->text('applicant_message')->nullable();
            $table->text('additional_info_requested')->nullable();

            $table->unsignedBigInteger('approved_delivery_person_id')->nullable()->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->foreign('approved_delivery_person_id')
                ->references('id')
                ->on('delivery_persons')
                ->nullOnDelete();
        });

        Schema::create('delivery_person_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('delivery_person_applications')
                ->cascadeOnDelete();
            $table->string('document_type', 50)->index();
            $table->string('original_name', 255);
            $table->string('disk', 40)->default('local');
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('replaced_by_id')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_person_application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('delivery_person_applications')
                ->cascadeOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('actor_type', 30)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_person_application_events');
        Schema::dropIfExists('delivery_person_application_documents');
        Schema::dropIfExists('delivery_person_applications');
    }
};
