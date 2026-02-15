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
        Schema::create('accessibility_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('url_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Audit configuration
            $table->string('wcag_level_target')->default('AA'); // A, AA, AAA
            $table->string('framework')->default('wcag21'); // wcag21, wcag22, section508, en301549, ada

            // Status tracking
            $table->string('status')->default('pending'); // pending, running, completed, failed, cancelled
            $table->text('error_message')->nullable();

            // Scoring
            $table->decimal('overall_score', 5, 2)->nullable(); // 0-100
            $table->json('scores_by_category')->nullable(); // {vision: 85, motor: 90, cognitive: 78, hearing: 95, general: 80}

            // Check counts
            $table->unsignedInteger('checks_total')->default(0);
            $table->unsignedInteger('checks_passed')->default(0);
            $table->unsignedInteger('checks_failed')->default(0);
            $table->unsignedInteger('checks_warning')->default(0);
            $table->unsignedInteger('checks_manual')->default(0);
            $table->unsignedInteger('checks_not_applicable')->default(0);

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Additional audit data (browser info, viewport, etc.)

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'created_at']);
            $table->index(['url_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessibility_audits');
    }
};
