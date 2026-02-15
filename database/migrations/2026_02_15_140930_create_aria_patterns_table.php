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
        Schema::create('aria_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('category'); // widget, composite, landmark, structure, live-region
            $table->json('required_roles'); // Roles that must be present
            $table->json('optional_roles')->nullable(); // Roles that may be present
            $table->json('required_attributes'); // Required ARIA attributes per role
            $table->json('optional_attributes')->nullable(); // Recommended ARIA attributes
            $table->json('keyboard_interactions'); // Required keyboard support
            $table->json('focus_management')->nullable(); // Focus management requirements
            $table->json('html_selectors'); // CSS selectors to detect this pattern
            $table->json('detection_rules'); // Rules for pattern detection
            $table->string('documentation_url'); // Link to WAI-ARIA APG
            $table->string('wcag_criteria')->nullable(); // Related WCAG success criteria
            $table->boolean('is_custom')->default(false); // Whether this is a custom pattern
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete(); // For custom patterns
            $table->timestamps();

            $table->index('category');
            $table->index('is_custom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aria_patterns');
    }
};
