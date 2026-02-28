<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Laravel\Socialite\Facades\Socialite;

class AuthCustomController extends BaseController
{
    /**
     * Register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role ?? 'learner');

        $token = $user->createToken('api_token')->plainTextToken;
        $roles = $user->getRoleNames();

        return $this->sendResponse([
            'user'  => $user,
            'token' => $token,
            'roles' => $roles,
        ], 'User registered successfully.');
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api_token')->plainTextToken;
        $roles = $user->getRoleNames();

        return $this->sendResponse([
            'user'  => $user->only(['name', 'email']),
            'roles' => $roles,
            'token' => $token,
        ], 'Login successful.');
    }

    public function getLearningResults($user)
    {
        return [
            'vark' => optional($user->varkResult)->dominant_style,
            'vark_scores' => [
                'score_v' => optional($user->varkResult)->score_v,
                'score_a' => optional($user->varkResult)->score_a,
                'score_r' => optional($user->varkResult)->score_r,
                'score_k' => optional($user->varkResult)->score_k,
            ],
            'math' => optional($user->mathResult)->level,
            'total_score' => optional($user->mathResult)->total_score,
        ];
    }

    /**
     * Profile
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
        $roles = $user->getRoleNames();
        $learningResults = $this->getLearningResults($user);

        // Load onboarding flags relationship
        $user->load('onboardingFlags');

        // Get or create onboarding flags
        $onboardingFlags = $user->onboardingFlags;
        if (!$onboardingFlags) {
            $onboardingFlags = \App\Models\OnboardingFlag::create([
                'user_id' => $user->id,
                'onboarding_dashboard' => false,
                'onboarding_mathpractice' => false,
                'onboarding_mathquest' => false,
                'profile' => false,
            ]);
        }

        // Calculate exp info for response
        $currentExp = $user->exp ?? 0;
        $currentLevel = $user->level ?? 1;
        
        // Calculate exp in current level (exp earned in current level only)
        $totalExpForCurrentLevel = \App\Models\User::getTotalExpForLevel($currentLevel);
        $expInCurrentLevel = max(0, $currentExp - $totalExpForCurrentLevel);
        
        // Calculate exp needed to go from current level to next level
        $expNeededForNextLevel = $currentLevel < 12 ? \App\Models\User::getExpNeededForLevel($currentLevel) : null;
        
        // Calculate total exp needed to reach next level (from level 1)
        $totalExpForNextLevel = $currentLevel < 12 ? \App\Models\User::getTotalExpForLevel($currentLevel + 1) : null;
        
        // Calculate progress: exp in current level / exp needed for next level
        $expProgress = $expNeededForNextLevel && $expNeededForNextLevel > 0 ? ($expInCurrentLevel / $expNeededForNextLevel) * 100 : 0;

        return $this->sendResponse([
            'user'    => $user->only(['name', 'email', 'has_completed_profiling', 'level', 'exp']),
            'roles'   => $roles,
            'learning_results' => $learningResults,
            'onboarding_flags' => [
                'onboarding_dashboard' => $onboardingFlags->onboarding_dashboard,
                'onboarding_mathpractice' => $onboardingFlags->onboarding_mathpractice,
                'onboarding_mathquest' => $onboardingFlags->onboarding_mathquest,
                'profile' => $onboardingFlags->profile,
            ],
            'exp_info' => [
                'exp' => $currentExp,
                'exp_in_current_level' => $expInCurrentLevel,
                'exp_needed_for_next_level' => $expNeededForNextLevel,
                'total_exp_for_next_level' => $totalExpForNextLevel,
                'exp_progress' => round($expProgress, 2),
            ],
        ], 'Profile retrieved successfully.');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->sendResponse([], 'Logged out successfully.');
    }

    /**
     * Onboarding
     */

    public function onboarding(Request $request)
    {
        $request->validate([
            'role' => 'required|string|in:' . Role::pluck('name')->join(','),
        ]);

        $user = $request->user();
        $user->role = $request->role;
        $user->save();

        return $this->sendResponse([
            'user' => $user
        ], 'Role assigned successfully.');
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // 'stateless()' disables using session state. It is useful in APIs where session is not used.
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if user exists with this google_id
            $user = User::where('google_id', $googleUser->getId())->first();

            // If user doesn't exist, check if email exists
            if (!$user) {
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    // Link Google account to existing user
                    $user->google_id = $googleUser->getId();
                    $user->save();
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'email_verified_at' => now(),
                        'password' => null, // No password for OAuth users
                    ]);

                    // Assign default role
                    $user->assignRole('learner');
                }
            }

            // Generate token
            $token = $user->createToken('api_token')->plainTextToken;
            $roles = $user->getRoleNames();

            // Get frontend URL from environment or use default
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:8080');
            $redirectUrl = $frontendUrl . '/auth/google/callback?token=' . $token . '&user=' . base64_encode(json_encode([
                'name' => $user->name,
                'email' => $user->email,
            ]));

            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Google OAuth error: ' . $e->getMessage());
            
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:8080');
            $errorUrl = $frontendUrl . '/auth/google/callback?error=' . urlencode('Authentication failed. Please try again.');
            
            return redirect($errorUrl);
        }
    }

}
