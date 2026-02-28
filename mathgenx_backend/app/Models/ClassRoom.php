<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasUuids;

    protected $table = 'class_rooms';

    protected $fillable = [
        'name',
        'code',
        'description',
        'educator_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function educator()
    {
        return $this->belongsTo(User::class, 'educator_id', 'id');
    }
}
