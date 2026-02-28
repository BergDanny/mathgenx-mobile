<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DskpCriteria extends Model
{
    use HasUuids;

    protected $table = 'dskp_criterias';
    
    protected $fillable = [
        'code',
        'name',
        'description',
        'subtopic_id',
    ];

    public function subtopic()
    {
        return $this->belongsTo(DskpSubtopic::class, 'subtopic_id', 'id');
    }

    public function teachingContents()
    {
        return $this->hasMany(TeachingContent::class, 'criteria_id', 'id');
    }

    public function assessmentContents()
    {
        return $this->hasMany(AssessmentContent::class, 'criteria_id', 'id');
    }
}
