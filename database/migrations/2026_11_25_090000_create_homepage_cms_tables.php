<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_contents')) {
            Schema::create('homepage_contents', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 60)->unique();
                $table->json('draft_payload');
                $table->json('published_payload')->nullable();
                $table->foreignId('last_draft_saved_by_admin_id')
                    ->nullable()
                    ->constrained('admins', 'id', 'hc_draft_admin_fk')
                    ->nullOnDelete();
                $table->timestamp('last_draft_saved_at')->nullable();
                $table->foreignId('published_by_admin_id')
                    ->nullable()
                    ->constrained('admins', 'id', 'hc_pub_admin_fk')
                    ->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->unsignedInteger('current_version_number')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('homepage_content_versions')) {
            Schema::create('homepage_content_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('homepage_content_id')
                    ->constrained('homepage_contents', 'id', 'hcv_content_fk')
                    ->cascadeOnDelete();
                $table->unsignedInteger('version_number');
                $table->string('action', 30);
                $table->string('section_key', 60)->nullable();
                $table->json('payload');
                $table->string('notes', 255)->nullable();
                $table->foreignId('created_by_admin_id')
                    ->nullable()
                    ->constrained('admins', 'id', 'hcv_admin_fk')
                    ->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['homepage_content_id', 'version_number'], 'hcv_content_ver_idx');
            });
        }

        if (! Schema::hasTable('homepage_media')) {
            Schema::create('homepage_media', function (Blueprint $table) {
                $table->id();
                $table->string('filename', 180);
                $table->string('disk', 30)->default('public');
                $table->string('path', 255)->unique();
                $table->string('mime_type', 80);
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('alt_text', 255)->nullable();
                $table->foreignId('uploaded_by_admin_id')
                    ->nullable()
                    ->constrained('admins', 'id', 'hm_admin_fk')
                    ->nullOnDelete();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_media');
        Schema::dropIfExists('homepage_content_versions');
        Schema::dropIfExists('homepage_contents');
    }
};
