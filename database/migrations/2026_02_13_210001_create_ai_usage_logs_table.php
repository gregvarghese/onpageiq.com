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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            // Provider and model info
            $table->string('provider'); // openai, anthropic, etc.
            $table->string('model'); // gpt-4o-mini, gpt-4o, etc.

            // Token usage
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);

            // Cost tracking
            $table->decimal('cost', 10, 6)->default(0);

            // Performance
            $table->unsignedInteger('duration_ms')->default(0);

            // Content storage (auto-redacted)
            $table->longText('prompt_content')->nullable();
            $table->longText('response_content')->nullable();
            $table->boolean('content_redacted')->default(false);
            $table->json('redaction_summary')->nullable();

            // Purpose tracking
            $table->string('category')->nullable(); // AIUsageCategory enum
            $table->string('purpose_detail')->nullable(); // Free-form detail
            $table->string('task_type')->nullable(); // Legacy field

            // Polymorphic relation for loggable
            $table->string('loggable_type')->nullable();
            $table->unsignedBigInteger('loggable_id')->nullable();

            // Status tracking
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();

            // Budget override tracking
            $table->boolean('budget_override')->default(false);
            $table->foreignId('budget_override_by')->nullable()->constrained('users')->nullOnDelete();

            // Additional metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['organization_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['provider', 'model']);
            $table->index('category');
            $table->index(['loggable_type', 'loggable_id']);
            $table->index('success');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
