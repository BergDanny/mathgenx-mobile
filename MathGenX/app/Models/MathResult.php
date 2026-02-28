<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MathResult extends Model
{
    use HasUuids;

    protected $table = 'math_results';
    
    protected $fillable = ['user_id', 'total_score', 'total_questions', 'level'];

    public function user() 
    { 
        return $this->belongsTo(User::class); 
    }
}
