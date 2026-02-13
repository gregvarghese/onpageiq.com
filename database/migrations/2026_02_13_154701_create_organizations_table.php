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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            // Subscription & billing
            $table->string('subscription_tier')->default('free'); // free, pro, team, enterprise
            $table->string('stripe_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            // Credits
            $table->integer('credit_balance')->default(5); // Free tier starts with 5 credits
            $table->integer('overdraft_balance')->default(0);
            $table->boolean('free_credits_used')->default(false); // Track one-time free credits

            // Settings (JSON for flexible configuration)
            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
