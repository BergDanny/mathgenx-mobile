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
        Schema::create('vark_answers', function (Blueprint $table) {
            $table->id();
            $table->string('option_letter', 1); // a/b/c/d
            $table->text('answer_text');
            $table->string('category', 1); // V/A/R/K

            $table->foreignId('question_id')->constrained('vark_questions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vark_answers');
    }
};
