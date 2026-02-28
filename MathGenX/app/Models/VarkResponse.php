<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarkResponse extends Model
{
    protected $table = 'vark_responses';

    protected $fillable = ['user_id', 'question_id', 'answer_id'];

    public function question()
    {
        return $this->belongsTo(VarkQuestion::class, 'question_id');
    }

    public function answer()
    {
        return $this->belongsTo(VarkAnswer::class, 'answer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
