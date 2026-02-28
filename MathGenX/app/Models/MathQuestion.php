<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MathQuestion extends Model
{
    protected $table = 'math_questions';

    protected $fillable = ['question_text', 'skill_description', 'criteria_id'];

    public function answers()
    {
        return $this->hasMany(MathAnswer::class, 'question_id');
    }

    public function responses()
    {
        return $this->hasMany(MathResponse::class, 'question_id');
    }
}
