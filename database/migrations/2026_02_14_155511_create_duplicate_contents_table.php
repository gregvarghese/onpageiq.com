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
        Schema::create('duplicate_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('url_id')->constrained()->cascadeOnDelete();
            $table->foreignId('duplicate_url_id')->constrained('urls')->cascadeOnDelete();
            $table->text('content_snippet');
            $table->decimal('similarity_score', 5, 4);
            $table->boolean('is_excluded')->default(false);
            $table->timestamps();

            $table->index(['scan_id', 'similarity_score']);
            $table->index(['url_id', 'duplicate_url_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duplicate_contents');
    }
};
