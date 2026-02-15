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
        Schema::create('manual_test_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accessibility_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vpat_evaluation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tester_user_id')->constrained('users')->cascadeOnDelete();

            // Test identification
            $table->string('criterion_id'); // WCAG criterion e.g., "1.1.1"
            $table->string('wcag_level'); // A, AA, AAA
            $table->string('test_name');
            $table->text('test_description');

            // Test procedure
            $table->json('test_steps'); // Array of steps to perform
            $table->json('expected_results'); // What should happen

            // Results
            $table->string('status')->default('pending'); // pending, in_progress, passed, failed, blocked, skipped
            $table->text('actual_results')->nullable();
            $table->text('tester_notes')->nullable();

            // Environment
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('assistive_technology')->nullable(); // NVDA, JAWS, VoiceOver, etc.
            $table->string('operating_system')->nullable();

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['accessibility_audit_id', 'criterion_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_test_checklists');
    }
};
