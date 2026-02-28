<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MathAnswer extends Model
{
    protected $table = 'math_answers';
    protected $fillable = ['question_id', 'option_letter', 'answer_text', 'is_correct'];

    public function question()
    {
        return $this->belongsTo(MathQuestion::class, 'question_id');
    }
}
