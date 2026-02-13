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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_result_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('severity');
            $table->text('text_excerpt');
            $table->text('suggestion')->nullable();
            $table->string('dom_selector')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->json('position')->nullable();
            $table->timestamps();

            $table->index(['scan_result_id', 'category']);
            $table->index(['scan_result_id', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
