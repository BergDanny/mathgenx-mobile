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
        Schema::create('practice_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary(); // message ID
            $table->uuid('practice_session_id'); // groups messages
            $table->string('question_id'); // per question
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade'); // multi-user safe
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->timestamps();
        
            $table->index(['practice_session_id', 'question_id', 'user_id'], 'pcm_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_chat_messages');
    }
};
