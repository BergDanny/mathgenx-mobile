<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarkAnswer extends Model
{
    protected $table = 'vark_answers';

    protected $fillable = ['question_id', 'option_letter', 'answer_text', 'category'];

    public function question()
    {
        return $this->belongsTo(VarkQuestion::class, 'question_id');
    }

    public function responses()
    {
        return $this->hasMany(VarkResponse::class, 'answer_id');
    }
}
