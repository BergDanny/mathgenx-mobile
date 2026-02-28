<?php

namespace App\Models;

use App\Enums\DskpMainLevel;
use App\Enums\DskpMainSubject;
use App\Enums\DskpMainType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DskpMain extends Model
{
    use HasUuids;

    protected $table = 'dskp_mains';

    protected $fillable = [
        'name',
        'code',
        'description',
        'subject',
        'level',
        'type',
        'issue',
        'remark',
    ];

    protected $casts = [
        'code' => 'integer',
        'subject' => DskpMainSubject::class,
        'type' => DskpMainType::class,
        'level' => DskpMainLevel::class,
        'issue' => 'integer',
    ];

    public function topics()
    {
        return $this->hasMany(DskpTopic::class, 'dskp_id');
    }
}
