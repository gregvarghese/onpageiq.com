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
        Schema::create('ai_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Budget limits
            $table->decimal('monthly_limit', 10, 2)->nullable(); // null = unlimited
            $table->decimal('warning_threshold', 5, 2)->default(80.00); // Percentage

            // Current period tracking (denormalized for performance)
            $table->decimal('current_month_usage', 12, 6)->default(0);
            $table->date('current_period_start')->nullable();

            // Budget controls
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_override')->default(true);

            $table->timestamps();

            // Unique constraint: one budget per org+user combination
            $table->unique(['organization_id', 'user_id'], 'ai_budgets_unique');

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_budgets');
    }
};
