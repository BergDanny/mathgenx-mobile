<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasUuids;

    protected $table = 'quiz_attempts';

    protected $fillable = [
        'user_id',
        'topic_id',
        'subtopic_id',
        'question_format',
        'language',
        'mastery_level',
        'learning_style',
        'total_questions',
        'correct_answers',
        'incorrect_answers',
        'score_percentage',
        'total_marks',
        'marks_obtained',
        'exp_gained',
        'started_at',
        'completed_at',
        'time_spent_seconds',
        'quiz_parameters',
        'source',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'quiz_parameters' => 'array',
        'score_percentage' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuizResponse::class, 'quiz_attempt_id');
    }

    // Helper methods
    public function calculateScorePercentage(): float
    {
        if ($this->total_questions === 0) {
            return 0;
        }
        return ($this->correct_answers / $this->total_questions) * 100;
    }

    public function getTimeSpentInMinutes(): float
    {
        return $this->time_spent_seconds ? ($this->time_spent_seconds / 60) : 0;
    }
}