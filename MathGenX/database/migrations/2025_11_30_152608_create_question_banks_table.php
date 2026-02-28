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
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->enum('question_format', ['multiple_choice', 'subjective']);
            $table->string('topic_id');
            $table->string('subtopic_id');
            $table->string('mastery_level');
            $table->string('learning_style');
            $table->enum('language', ['english', 'malay'])->default('english');
            
            // MCQ specific fields
            $table->json('choices')->nullable();
            $table->string('answer_key')->nullable();
            
            // Subjective specific fields
            $table->json('working_steps')->nullable();
            $table->text('final_answer')->nullable();
            $table->string('answer_type')->nullable();
            $table->json('accepted_variations')->nullable();
            $table->integer('total_marks')->nullable();
            
            $table->json('full_api_response');
            $table->timestamps();
            
            // INDEXES - Speed up common queries
            $table->index(['topic_id', 'subtopic_id']); // Composite index
            $table->index('question_format'); // Single column index
            $table->index(['mastery_level', 'learning_style', 'language']); // Composite index with language
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};