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
        Schema::create('page_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('url_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->decimal('lcp_score', 8, 2)->nullable();
            $table->decimal('fid_score', 8, 2)->nullable();
            $table->decimal('cls_score', 8, 4)->nullable();
            $table->unsignedInteger('load_time')->nullable();
            $table->unsignedBigInteger('page_size')->nullable();
            $table->unsignedInteger('request_count')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->decimal('flesch_kincaid_grade', 5, 2)->nullable();
            $table->decimal('flesch_reading_ease', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['url_id', 'scan_id']);
            $table->index('scan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_metrics');
    }
};
