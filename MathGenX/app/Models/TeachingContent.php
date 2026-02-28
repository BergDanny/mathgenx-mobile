<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeachingContent extends Model
{
    use HasUuids;

    protected $table = 'teaching_contents';

    protected $fillable = [
        'code',
        'page',
        'concept',
        'example',
        'question_number',
        'question_text',
        'calculation_step',
        'answer',
        'teaching_id',
        'criteria_id',
    ];

    public function teachingMaterial()
    {
        return $this->belongsTo(TeachingMaterial::class, 'teaching_id', 'id');
    }

    public function criteria()
    {
        return $this->belongsTo(DskpCriteria::class, 'criteria_id', 'id');
    }
}
