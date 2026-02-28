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
        Schema::create('teaching_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('page');
            $table->text('concept');
            $table->text('example');
            $table->text('question_number');
            $table->text('question_text');
            $table->text('calculation_step');
            $table->text('answer');
            $table->foreignUuid('criteria_id')->constrained('dskp_criterias');
            $table->foreignUuid('teaching_id')->constrained('teaching_materials');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_contents');
    }
};
