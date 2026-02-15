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
        Schema::create('site_architectures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_nodes')->default(0);
            $table->unsignedInteger('total_links')->default(0);
            $table->unsignedInteger('max_depth')->default(0);
            $table->unsignedInteger('orphan_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('crawl_config')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_architectures');
    }
};
