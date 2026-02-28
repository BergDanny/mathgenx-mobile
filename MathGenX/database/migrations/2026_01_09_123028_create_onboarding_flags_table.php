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
        Schema::create('onboarding_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->boolean('onboarding_dashboard')->default(false);
            $table->boolean('onboarding_mathpractice')->default(false);
            $table->boolean('onboarding_mathquest')->default(false);
            $table->boolean('profile')->default(false);
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_flags');
    }
};
