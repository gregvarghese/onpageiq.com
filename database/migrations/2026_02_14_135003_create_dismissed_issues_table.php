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
        Schema::create('dismissed_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('url_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('dismissed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('scope', ['url', 'project', 'pattern'])->default('url');
            $table->string('category');
            $table->text('text_pattern');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'category']);
            $table->index(['project_id', 'category']);
            $table->index(['url_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dismissed_issues');
    }
};
