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
        Schema::create('schema_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('url_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('schema_type');
            $table->json('schema_data');
            $table->boolean('is_valid')->default(true);
            $table->json('validation_errors')->nullable();
            $table->boolean('rich_results_eligible')->default(false);
            $table->timestamps();

            $table->index(['url_id', 'scan_id']);
            $table->index(['scan_id', 'schema_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schema_validations');
    }
};
