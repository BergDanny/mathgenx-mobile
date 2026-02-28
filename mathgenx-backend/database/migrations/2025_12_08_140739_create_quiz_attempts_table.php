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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            // Quiz parameters
            $table->string('topic_id'); // From QuestionBank
            $table->string('subtopic_id')->nullable();
            $table->enum('question_format', ['multiple_choice', 'subjective']);
            $table->enum('language', ['english', 'malay'])->default('english');
            $table->string('mastery_level'); // TP1, TP2, etc.
            $table->string('learning_style'); // Visual, Auditory, etc.
            
            // Performance metrics
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('incorrect_answers')->default(0);
            $table->decimal('score_percentage', 5, 2)->default(0); // e.g., 85.50
            $table->integer('total_marks')->default(0);
            $table->integer('marks_obtained')->default(0);
            $table->integer('exp_gained')->default(0);
            // Time tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_seconds')->nullable(); // Total time in seconds
            
            // Metadata
            $table->json('quiz_parameters')->nullable(); // Store full request params
            $table->string('source')->default('api'); // 'api', 'database', 'database_fallback'
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['topic_id', 'subtopic_id']);
            $table->index('mastery_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};