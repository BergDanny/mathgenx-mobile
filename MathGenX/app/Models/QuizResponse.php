<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizResponse extends Model
{
    use HasUuids;

    protected $table = 'quiz_responses';

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'user_id',
        'topic_id',
        'subtopic_id',
        'mastery_level',
        'learning_style',
        'question_format',
        'student_answer',
        'correct_answer',
        'is_correct',
        'marks_obtained',
        'total_marks',
        'time_spent_seconds',
        'attempt_number',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    // Relationships
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}