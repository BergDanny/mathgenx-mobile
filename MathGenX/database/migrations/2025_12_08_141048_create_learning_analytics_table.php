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
        Schema::create('learning_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            // Learning style effectiveness
            $table->string('learning_style'); // Visual, Auditory, etc.
            $table->string('topic_id')->nullable();
            
            // Performance by learning style
            $table->integer('attempts_count')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            
            // Question format performance
            $table->enum('question_format', ['multiple_choice', 'subjective'])->nullable();
            $table->decimal('format_average_score', 5, 2)->nullable();
            
            // Time of day / day of week (for pattern analysis)
            $table->string('preferred_time_slot')->nullable(); // morning, afternoon, evening
            $table->integer('questions_per_session_avg')->default(0);
            
            $table->timestamps();
            
            $table->index(['user_id', 'learning_style']);
            $table->index(['user_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_analytics');
    }
};