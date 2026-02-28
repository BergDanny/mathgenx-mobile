<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PracticeChatMessage extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'practice_session_id',
        'question_id',
        'user_id',
        'role',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
