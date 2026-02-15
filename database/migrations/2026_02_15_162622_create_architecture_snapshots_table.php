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
        Schema::create('architecture_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_architecture_id')->constrained()->cascadeOnDelete();
            $table->json('snapshot_data');
            $table->unsignedInteger('nodes_count')->default(0);
            $table->unsignedInteger('links_count')->default(0);
            $table->json('changes_summary')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['site_architecture_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('architecture_snapshots');
    }
};
