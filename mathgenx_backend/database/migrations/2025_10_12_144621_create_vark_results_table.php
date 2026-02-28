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
        Schema::create('vark_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('score_v')->default(0);
            $table->integer('score_a')->default(0);
            $table->integer('score_r')->default(0);
            $table->integer('score_k')->default(0);
            $table->string('dominant_style')->nullable(); 
            
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vark_results');
    }
};
