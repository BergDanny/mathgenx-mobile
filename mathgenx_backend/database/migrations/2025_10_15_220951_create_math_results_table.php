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
        Schema::create('math_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('total_score')->default(0);
            $table->string('level')->nullable(); // Beginner/Intermediate/Proficient/Advanced
            
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('math_results');
    }
};
