<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeachingMaterial extends Model
{
    use HasUuids;

    protected $table = 'teaching_materials';

    protected $fillable = [
        'code',
        'name',
        'issuer',
        'description',
        'remark',
        'file_path',
    ];

    public function teachingContents()
    {
        return $this->hasMany(TeachingContent::class, 'teaching_id', 'id');
    }
}
