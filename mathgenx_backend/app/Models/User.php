<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'has_completed_profiling',
        'level',
        'exp',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class, 'educator_id', 'id');
    }

    public function varkResult()
    {
        return $this->hasOne(VarkResult::class, 'user_id');
    }

    public function mathResult()
    {
        return $this->hasOne(MathResult::class, 'user_id');
    }

    public function mathResponses()
    {
        return $this->hasMany(MathResponse::class, 'user_id');
    }

    public function VarkResponses()
    {
        return $this->hasMany(VarkResponse::class, 'user_id');
    }

    public function onboardingFlags()
    {
        return $this->hasOne(OnboardingFlag::class, 'user_id');
    }

    /**
     * Calculate the exp needed to reach the next level
     * Level 1 → 2: 100 exp
     * Level 2 → 3: 120 exp (120% of 100)
     * Level 3 → 4: 144 exp (120% of 120)
     * Formula: exp_needed = 100 * (1.2 ^ (current_level - 1))
     */
    public static function getExpNeededForLevel(int $currentLevel): int
    {
        if ($currentLevel < 1) {
            return 100; // Default for level 1
        }
        
        // Level 1 → 2 needs 100 exp
        // Level 2 → 3 needs 120 exp (100 * 1.2)
        // Level 3 → 4 needs 144 exp (120 * 1.2)
        // Formula: 100 * (1.2 ^ (current_level - 1))
        return (int) round(100 * pow(1.2, $currentLevel - 1));
    }

    /**
     * Calculate the total exp needed to reach a specific level from level 1
     */
    public static function getTotalExpForLevel(int $targetLevel): int
    {
        if ($targetLevel <= 1) {
            return 0;
        }
        
        $totalExp = 0;
        for ($level = 1; $level < $targetLevel; $level++) {
            $totalExp += self::getExpNeededForLevel($level);
        }
        
        return $totalExp;
    }

    /**
     * Check if user has enough exp to level up and return new level
     */
    public function checkAndUpdateLevel(): ?int
    {
        $currentLevel = $this->level ?? 1;
        $currentExp = $this->exp ?? 0;
        
        // Can't level up beyond level 12
        if ($currentLevel >= 12) {
            return null;
        }
        
        // Calculate total exp needed for next level
        $totalExpForNextLevel = self::getTotalExpForLevel($currentLevel + 1);
        
        // Check if user has enough exp
        if ($currentExp >= $totalExpForNextLevel) {
            $newLevel = $currentLevel + 1;
            $this->update(['level' => $newLevel]);
            return $newLevel;
        }
        
        return null;
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // Get the current values (after potential changes)
            $password = $user->password ?? $user->getOriginal('password');
            $googleId = $user->google_id ?? $user->getOriginal('google_id');
            
            // Ensure at least one authentication method exists
            if (empty($password) && empty($googleId)) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['auth' => ['User must have either a password or google_id']]
                );
            }
        });
    }
}
