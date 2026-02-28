<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DskpMastery extends Model
{
    use HasUuids;

    protected $table = 'dskp_masteries';
    
    protected $fillable = [
        'code',
        'name',
        'description',
        'topic_id',
        'level_id',
    ];

    protected $casts = [
        'code' => 'integer',
    ];

    public function topic()
    {
        return $this->belongsTo(DskpTopic::class, 'topic_id', 'id');
    }
}
