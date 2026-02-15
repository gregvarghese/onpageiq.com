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
        Schema::create('project_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // form, oauth, api_key, cookie, session
            $table->text('credentials'); // Encrypted JSON containing auth details
            $table->string('login_url')->nullable();
            $table->json('login_steps')->nullable(); // Steps for form-based auth
            $table->json('headers')->nullable(); // Custom headers to include
            $table->json('cookies')->nullable(); // Cookies to set
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->text('validation_error')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'is_active']);
            $table->index(['project_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_credentials');
    }
};
