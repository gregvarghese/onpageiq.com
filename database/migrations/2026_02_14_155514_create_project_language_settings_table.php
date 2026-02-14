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
        Schema::create('project_language_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_language')->default('English');
            $table->string('regional_variant')->nullable();
            $table->unsignedInteger('target_reading_level')->nullable();
            $table->unsignedInteger('thin_content_threshold')->default(300);
            $table->unsignedInteger('stale_content_months')->default(12);
            $table->timestamps();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_language_settings');
    }
};
