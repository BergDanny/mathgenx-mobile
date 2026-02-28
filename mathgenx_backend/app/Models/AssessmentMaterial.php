<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssessmentMaterial extends Model
{
    use HasUuids;

    protected $table = 'assessment_materials';

    protected $fillable = [
        'code',
        'name',
        'issuer',
        'description',
        'remark',
        'file_path',
    ];

    public function assessmentContents()
    {
        return $this->hasMany(AssessmentContent::class, 'assessment_id', 'id');
    }
}
