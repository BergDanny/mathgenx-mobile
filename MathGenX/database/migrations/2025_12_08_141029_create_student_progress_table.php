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
        Schema::create('student_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('topic_id', 100);
            $table->string('subtopic_id', 100)->nullable();
            $table->string('mastery_level', 50)->nullable(); // Track per mastery level
            
            // Aggregated metrics
            $table->integer('total_attempts')->default(0);
            $table->integer('total_questions_answered')->default(0);
            $table->integer('total_correct')->default(0);
            $table->integer('total_incorrect')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->decimal('best_score', 5, 2)->default(0);
            $table->decimal('improvement_rate', 5, 2)->nullable(); // Percentage improvement
            
            // Time metrics
            $table->integer('total_time_spent_seconds')->default(0);
            $table->integer('average_time_per_question')->default(0);
            
            $table->timestamp('first_attempt_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            
            $table->timestamps();
            
            // Unique constraint - one record per user/topic/subtopic/mastery_level
            $table->unique(['user_id', 'topic_id', 'subtopic_id', 'mastery_level'], 'unique_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};