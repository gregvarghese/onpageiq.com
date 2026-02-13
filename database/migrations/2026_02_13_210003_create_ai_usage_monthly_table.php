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
        Schema::create('ai_usage_monthly', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();

            // Aggregated counts
            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);

            // Aggregated tokens and cost
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->decimal('total_cost', 14, 6)->default(0);

            $table->timestamps();

            // Unique constraint for upsert operations
            $table->unique(
                ['year', 'month', 'organization_id', 'user_id', 'category'],
                'ai_usage_monthly_unique'
            );

            // Indexes for dashboard queries
            $table->index(['year', 'month', 'organization_id']);
            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_monthly');
    }
};
