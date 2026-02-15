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
        Schema::create('vpat_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accessibility_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // Product information
            $table->string('product_name');
            $table->string('product_version')->nullable();
            $table->text('product_description')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_contact')->nullable();
            $table->date('evaluation_date');
            $table->text('evaluation_methods')->nullable();

            // VPAT version and report type
            $table->string('vpat_version')->default('2.4');
            $table->json('report_types'); // ['wcag21', 'section508', 'en301549']

            // Criteria evaluations stored as JSON
            // Structure: { "1.1.1": { "level": "supports", "remarks": "..." }, ... }
            $table->json('wcag_evaluations')->nullable();
            $table->json('section508_evaluations')->nullable();
            $table->json('en301549_evaluations')->nullable();

            // Summary and notes
            $table->text('legal_disclaimer')->nullable();
            $table->text('notes')->nullable();

            // Status
            $table->string('status')->default('draft'); // draft, in_review, approved, published
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('evaluation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vpat_evaluations');
    }
};
