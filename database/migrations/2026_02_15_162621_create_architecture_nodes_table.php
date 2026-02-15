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
        Schema::create('architecture_nodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_architecture_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('path', 1024);
            $table->string('title', 512)->nullable();
            $table->string('status')->default('ok');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->unsignedInteger('inbound_count')->default(0);
            $table->unsignedInteger('outbound_count')->default(0);
            $table->decimal('link_equity_score', 10, 6)->default(0);
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('issues_count')->default(0);
            $table->boolean('is_orphan')->default(false);
            $table->boolean('is_deep')->default(false);
            $table->json('metadata')->nullable();
            $table->decimal('position_x', 12, 4)->nullable();
            $table->decimal('position_y', 12, 4)->nullable();
            $table->timestamps();

            $table->index(['site_architecture_id', 'status']);
            $table->index(['site_architecture_id', 'depth']);
            $table->index(['site_architecture_id', 'is_orphan']);
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('architecture_nodes');
    }
};
