<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VarkResult extends Model
{
    use HasUuids;

    protected $table = 'vark_results';

    protected $fillable = [
        'user_id',
        'score_v',
        'score_a',
        'score_r',
        'score_k',
        'dominant_style',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
