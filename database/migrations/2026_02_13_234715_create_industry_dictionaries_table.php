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
        Schema::create('industry_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('industry_dictionary_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_dictionary_id')->constrained()->cascadeOnDelete();
            $table->string('word', 100);
            $table->timestamps();

            $table->unique(['industry_dictionary_id', 'word']);
        });

        // Pivot table for projects to enable specific industry dictionaries
        Schema::create('project_industry_dictionary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_dictionary_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'industry_dictionary_id'], 'project_industry_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_industry_dictionary');
        Schema::dropIfExists('industry_dictionary_words');
        Schema::dropIfExists('industry_dictionaries');
    }
};
