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
        Schema::create('architecture_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_architecture_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('source_node_id')->constrained('architecture_nodes')->cascadeOnDelete();
            $table->uuid('target_node_id')->nullable();
            $table->string('target_url', 2048)->nullable();
            $table->string('link_type')->default('unknown');
            $table->string('link_type_override')->nullable();
            $table->string('anchor_text', 512)->nullable();
            $table->boolean('is_external')->default(false);
            $table->string('external_domain', 255)->nullable();
            $table->boolean('is_nofollow')->default(false);
            $table->string('position_in_page')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['site_architecture_id', 'link_type']);
            $table->index(['site_architecture_id', 'is_external']);
            $table->index(['source_node_id']);
            $table->index(['target_node_id']);

            $table->foreign('target_node_id')
                ->references('id')
                ->on('architecture_nodes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('architecture_links');
    }
};
