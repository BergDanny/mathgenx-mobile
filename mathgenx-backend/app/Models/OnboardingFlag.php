<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingFlag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'onboarding_dashboard',
        'onboarding_mathpractice',
        'onboarding_mathquest',
        'profile',
    ];

    protected $casts = [
        'onboarding_dashboard' => 'boolean',
        'onboarding_mathpractice' => 'boolean',
        'onboarding_mathquest' => 'boolean',
        'profile' => 'boolean',
    ];

    /**
     * Get the user that owns the onboarding flags.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
