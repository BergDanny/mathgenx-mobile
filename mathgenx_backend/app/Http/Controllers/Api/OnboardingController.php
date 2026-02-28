<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\OnboardingFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OnboardingController extends BaseController
{
    /**
     * Get the current user's onboarding status
     */
    public function getOnboardingStatus(Request $request)
    {
        $user = $request->user();
        
        // Get or create onboarding flags for the user
        $onboardingFlags = OnboardingFlag::firstOrCreate(
            ['user_id' => $user->id],
            [
                'onboarding_dashboard' => false,
                'onboarding_mathpractice' => false,
                'onboarding_mathquest' => false,
                'profile' => false,
            ]
        );

        return $this->sendResponse([
            'onboarding_flags' => [
                'onboarding_dashboard' => $onboardingFlags->onboarding_dashboard,
                'onboarding_mathpractice' => $onboardingFlags->onboarding_mathpractice,
                'onboarding_mathquest' => $onboardingFlags->onboarding_mathquest,
                'profile' => $onboardingFlags->profile,
            ]
        ], 'Onboarding status retrieved successfully.');
    }

    /**
     * Mark a specific onboarding type as complete
     */
    public function completeOnboarding(Request $request)
    {
        $request->validate([
            'type' => [
                'required',
                'string',
                Rule::in(['onboarding_dashboard', 'onboarding_mathpractice', 'onboarding_mathquest', 'profile'])
            ],
        ]);

        $user = $request->user();
        $type = $request->input('type');

        DB::beginTransaction();
        try {
            // Get or create onboarding flags for the user
            $onboardingFlags = OnboardingFlag::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'onboarding_dashboard' => false,
                    'onboarding_mathpractice' => false,
                    'onboarding_mathquest' => false,
                    'profile' => false,
                ]
            );

            // Update the specific onboarding flag
            $onboardingFlags->update([$type => true]);

            DB::commit();

            return $this->sendResponse([
                'onboarding_flags' => [
                    'onboarding_dashboard' => $onboardingFlags->onboarding_dashboard,
                    'onboarding_mathpractice' => $onboardingFlags->onboarding_mathpractice,
                    'onboarding_mathquest' => $onboardingFlags->onboarding_mathquest,
                    'profile' => $onboardingFlags->profile,
                ]
            ], 'Onboarding marked as complete successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to update onboarding status.', [$e->getMessage()]);
        }
    }
}
