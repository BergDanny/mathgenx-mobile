<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarkQuestion extends Model
{
    protected $table = 'vark_questions';

    protected $fillable = ['order_number', 'question_text'];

    public function answers()
    {
        return $this->hasMany(VarkAnswer::class, 'question_id');
    }

    public function responses()
    {
        return $this->hasMany(VarkResponse::class, 'question_id');
    }
}
