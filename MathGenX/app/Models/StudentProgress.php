<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    use HasUuids;

    protected $table = 'student_progress';

    protected $fillable = [
        'user_id',
        'topic_id',
        'subtopic_id',
        'mastery_level',
        'total_attempts',
        'total_questions_answered',
        'total_correct',
        'total_incorrect',
        'average_score',
        'best_score',
        'improvement_rate',
        'total_time_spent_seconds',
        'average_time_per_question',
        'first_attempt_at',
        'last_attempt_at',
    ];

    protected $casts = [
        'average_score' => 'decimal:2',
        'best_score' => 'decimal:2',
        'improvement_rate' => 'decimal:2',
        'first_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function calculateImprovementRate($previousAverageScore): float
    {
        if ($previousAverageScore == 0) {
            return 0;
        }
        return (($this->average_score - $previousAverageScore) / $previousAverageScore) * 100;
    }
}