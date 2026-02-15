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
        Schema::create('compliance_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // DeadlineType enum
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('deadline_date');
            $table->string('wcag_level_target')->nullable(); // A, AA, AAA
            $table->decimal('score_target', 5, 2)->nullable(); // Target accessibility score
            $table->json('reminder_days')->nullable(); // Days before deadline to send reminders
            $table->json('notified_days')->nullable(); // Days we've already sent reminders for
            $table->boolean('is_active')->default(true);
            $table->boolean('is_met')->default(false);
            $table->timestamp('met_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_active', 'deadline_date']);
            $table->index(['deadline_date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_deadlines');
    }
};
