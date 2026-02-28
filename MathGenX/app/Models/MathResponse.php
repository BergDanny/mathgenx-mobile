<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MathResponse extends Model
{
    protected $fillable = ['user_id', 'question_id', 'answer_id', 'is_correct'];

    public function question() 
    {
        return $this->belongsTo(MathQuestion::class); 
    }

    public function answer() {
        return $this->belongsTo(MathAnswer::class, 'answer_id');
    }

    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}
