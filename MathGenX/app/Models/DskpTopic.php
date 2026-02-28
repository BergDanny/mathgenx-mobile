<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DskpTopic extends Model
{
    use HasUuids;

    protected $table = 'dskp_topics';

    protected $fillable = [
        'code',
        'name',
        'description',
        'dskp_id',
    ];

    protected $casts = [
        'code' => 'integer',    
    ];

    public function dskpMain()
    {
        return $this->belongsTo(DskpMain::class, 'dskp_id', 'id');
    }

    public function subtopics()
    {
        return $this->hasMany(DskpSubtopic::class, 'topic_id', 'id');
    }

    public function masteries()
    {
        return $this->hasMany(DskpMastery::class, 'topic_id', 'id');
    }

}
