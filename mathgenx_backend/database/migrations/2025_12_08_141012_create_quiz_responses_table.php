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
        Schema::create('quiz_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_attempt_id')->constrained('quiz_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('question_banks')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            // Question details (denormalized for easier queries)
            $table->string('topic_id');
            $table->string('subtopic_id')->nullable();
            $table->string('mastery_level');
            $table->string('learning_style');
            $table->enum('question_format', ['multiple_choice', 'subjective']);
            
            // Student answer
            $table->text('student_answer')->nullable(); // For subjective or choice ID
            $table->text('correct_answer')->nullable(); // Store correct answer for reference
            $table->boolean('is_correct')->default(false);
            
            // Scoring
            $table->integer('marks_obtained')->default(0);
            $table->integer('total_marks')->default(0);
            
            // Time tracking per question
            $table->integer('time_spent_seconds')->nullable();
            
            // Attempt metadata
            $table->integer('attempt_number')->default(1); // If same question attempted multiple times
            $table->timestamp('answered_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'question_id']);
            $table->index(['quiz_attempt_id', 'is_correct']);
            $table->index(['topic_id', 'subtopic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_responses');
    }
};