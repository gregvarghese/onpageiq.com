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
        Schema::create('architecture_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_architecture_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('node_id')->nullable()->constrained('architecture_nodes')->nullOnDelete();
            $table->string('issue_type');
            $table->string('severity')->default('moderate');
            $table->text('message');
            $table->text('recommendation')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['site_architecture_id', 'issue_type']);
            $table->index(['site_architecture_id', 'severity']);
            $table->index(['site_architecture_id', 'is_resolved']);
            $table->index(['node_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('architecture_issues');
    }
};
