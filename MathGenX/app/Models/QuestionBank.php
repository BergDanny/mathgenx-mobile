<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $table = 'question_banks';

    protected $fillable = [
        'question_text',
        'question_format',
        'topic_id',
        'subtopic_id',
        'mastery_level',
        'learning_style',
        'language',
        'choices',
        'answer_key',
        'working_steps',
        'final_answer',
        'answer_type',
        'accepted_variations',
        'total_marks',
        'full_api_response',
    ];

    protected $casts = [
        'choices' => 'array',
        'working_steps' => 'array',
        'accepted_variations' => 'array',
        'full_api_response' => 'json',
    ];
}
