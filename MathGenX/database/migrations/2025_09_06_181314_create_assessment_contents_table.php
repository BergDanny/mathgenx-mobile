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
        Schema::create('assessment_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->text('concept');
            $table->text('example');
            $table->text('question_number');
            $table->text('question_text');
            $table->text('calculation_step');
            $table->text('answer');
            $table->string('learning_type');
            $table->string('file_path')->nullable();
            $table->foreignUuid('criteria_id')->constrained('dskp_criterias');
            $table->foreignUuid('assessment_id')->constrained('assessment_materials');
            $table->foreignUuid('mastery_id')->constrained('dskp_masteries');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_contents');
    }
};
