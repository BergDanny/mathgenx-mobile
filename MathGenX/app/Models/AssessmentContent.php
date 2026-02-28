<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssessmentContent extends Model
{
    use HasUuids;

    protected $table = 'assessment_contents';

    protected $fillable = [
        'code',
        'concept',
        'example',
        'question_number',
        'question_text',
        'calculation_step',
        'answer',
        'criteria_id',
        'assessment_id',
        'mastery_id',
    ];

    public function criteria()
    {
        return $this->belongsTo(DskpCriteria::class, 'criteria_id', 'id');
    }

    public function assessmentMaterial()
    {
        return $this->belongsTo(AssessmentMaterial::class, 'assessment_id', 'id');
    }
    public function mastery()
    {
        return $this->belongsTo(DskpMastery::class, 'mastery_id', 'id');
    }
}
