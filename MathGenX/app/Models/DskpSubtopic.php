<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DskpSubtopic extends Model
{
    use HasUuids;

    protected $table = 'dskp_subtopics';

    protected $fillable = [
        'code',
        'name',
        'description',
        'topic_id',
    ];

    public function topic()
    {
        return $this->belongsTo(DskpTopic::class, 'topic_id', 'id');
    }

    public function criterias()
    {
        return $this->hasMany(DskpCriteria::class, 'subtopic_id', 'id');
    }
}
