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
        Schema::create('broken_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('url_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('link_url');
            $table->string('link_text')->nullable();
            $table->enum('link_type', ['internal', 'external', 'anchor']);
            $table->unsignedInteger('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['url_id', 'scan_id']);
            $table->index(['scan_id', 'link_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broken_links');
    }
};
