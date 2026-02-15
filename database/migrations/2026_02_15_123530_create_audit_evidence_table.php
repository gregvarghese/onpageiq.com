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
        Schema::create('audit_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('captured_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Evidence type
            $table->string('type'); // screenshot, recording, note, link, document

            // Content
            $table->string('file_path')->nullable(); // Path to stored file
            $table->string('external_url')->nullable(); // External resource URL
            $table->text('notes')->nullable(); // Tester notes
            $table->string('title')->nullable(); // Brief title for the evidence

            // File metadata
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // Timing
            $table->timestamp('captured_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('audit_check_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_evidence');
    }
};
