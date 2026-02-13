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
        Schema::create('ai_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();

            // Aggregated counts
            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);

            // Aggregated tokens
            $table->unsignedBigInteger('total_prompt_tokens')->default(0);
            $table->unsignedBigInteger('total_completion_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);

            // Aggregated cost and duration
            $table->decimal('total_cost', 12, 6)->default(0);
            $table->unsignedBigInteger('total_duration_ms')->default(0);

            $table->timestamps();

            // Unique constraint for upsert operations
            $table->unique(
                ['date', 'organization_id', 'user_id', 'category', 'provider', 'model'],
                'ai_usage_daily_unique'
            );

            // Indexes for dashboard queries
            $table->index(['date', 'organization_id']);
            $table->index(['date', 'provider']);
            $table->index(['date', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_daily');
    }
};
