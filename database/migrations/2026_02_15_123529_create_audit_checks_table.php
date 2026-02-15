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
        Schema::create('audit_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accessibility_audit_id')->constrained()->cascadeOnDelete();

            // WCAG criterion identification
            $table->string('criterion_id', 10); // e.g., "1.4.3", "2.4.7"
            $table->string('criterion_name'); // e.g., "Contrast (Minimum)"
            $table->string('wcag_level', 3); // A, AA, AAA

            // Classification
            $table->string('category'); // vision, motor, cognitive, hearing, general
            $table->string('impact')->nullable(); // critical, serious, moderate, minor

            // Result
            $table->string('status'); // pass, fail, warning, manual_review, not_applicable, opportunity

            // Element details (for failures)
            $table->string('element_selector')->nullable(); // CSS selector
            $table->text('element_html')->nullable(); // Captured HTML snippet
            $table->string('element_xpath')->nullable(); // XPath for precision

            // Messages
            $table->text('message')->nullable(); // Issue description
            $table->text('suggestion')->nullable(); // Fix recommendation
            $table->text('code_snippet')->nullable(); // Example fix code

            // Documentation
            $table->string('documentation_url')->nullable(); // Link to WCAG docs
            $table->string('technique_id')->nullable(); // WCAG technique ID (e.g., "G18")

            // Issue tracking
            $table->string('fingerprint')->nullable(); // Stable identifier for regression tracking
            $table->boolean('is_recurring')->default(false); // True if seen in previous audits

            // Additional data
            $table->json('metadata')->nullable(); // Additional check-specific data

            $table->timestamps();

            // Indexes
            $table->index(['accessibility_audit_id', 'status']);
            $table->index(['accessibility_audit_id', 'wcag_level']);
            $table->index(['accessibility_audit_id', 'category']);
            $table->index(['criterion_id', 'status']);
            $table->index('fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_checks');
    }
};
