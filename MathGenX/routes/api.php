<?php

use App\Http\Controllers\Api\AuthCustomController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\BillplzController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ProfilingController;
use App\Http\Controllers\Api\MathQuestController;
use App\Http\Controllers\Api\MathPracticeController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 'throttle:60,1' means a maximum of 60 requests per minute per user/IP.
Route::prefix('v1')->as('api.v1.')->middleware('throttle:60,1')->group(function () {

    Route::prefix('billplz')->as('billplz.')->group(function () {
        Route::get('redirect', [BillplzController::class, 'redirect'])->name('redirect');
        Route::post('callback', [BillplzController::class, 'callback'])->name('callback');
    });

    Route::prefix('auth')->as('auth.')->group(function () {
        Route::prefix('billplz')->as('billplz.')->group(function () {
            Route::post('billing', [BillplzController::class, 'createBill'])->name('billing');
            Route::get('/billing/{billId}', [BillplzController::class, 'getBill'])->name('getBill');
        });

        Route::post('register', [AuthCustomController::class, 'register']);
        Route::post('login', [AuthCustomController::class, 'login']);
        
        // Google OAuth routes - using stateless mode (no sessions required)
        Route::get('google/redirect', [AuthCustomController::class, 'redirectToGoogle']);
        Route::get('google/callback', [AuthCustomController::class, 'handleGoogleCallback']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('profile', [AuthCustomController::class, 'profile']);
            Route::post('logout', [AuthCustomController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->prefix('profiling')->group(function () {
        // VARK
        Route::get('/vark/questions', [ProfilingController::class, 'getVarkQuestions']);
        Route::post('/vark/submit', [ProfilingController::class, 'submitVark']);
        // Math
        Route::get('/math/questions', [ProfilingController::class, 'getMathQuestions']);
        Route::post('/math/submit', [ProfilingController::class, 'submitMath']);

        Route::post('/complete', [ProfilingController::class, 'completeProfiling']);
        Route::post('/submit', [ProfilingController::class, 'submitAll']);
        Route::get('/responses', [ProfilingController::class, 'getUserProfilingResponses']);
    });

    // MathQuest - separate from profiling
    Route::middleware('auth:sanctum')->prefix('mathquest')->group(function () {
        Route::get('/quiz', [MathQuestController::class, 'generateQuestion']);
        Route::post('/attempts', [MathQuestController::class, 'submitAttempt']);
        Route::get('/attempts', [MathQuestController::class, 'getAttempts']);
        Route::get('/attempts/{id}', [MathQuestController::class, 'getAttemptDetails']);
    });

    // MathPractice - practice questions from question bank
    Route::middleware('auth:sanctum')->prefix('mathpractice')->group(function () {
        Route::get('/questions', [MathPracticeController::class, 'getQuestions']);
        Route::post('/chatbot/message', [ChatbotController::class, 'sendMessage']);
        Route::get('/chatbot/history', [ChatbotController::class, 'getChatHistory']);
    });

    // Analytics - student progress
    Route::middleware('auth:sanctum')->prefix('analytics')->group(function () {
        Route::get('/progress', [AnalyticsController::class, 'getStudentProgress']);
        Route::get('/dashboard', [AnalyticsController::class, 'getDashboardAnalytics']);
    });

    Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
        Route::put('/update', [ProfileController::class, 'updateProfile']);
    });

    // Onboarding
    Route::middleware('auth:sanctum')->prefix('onboarding')->group(function () {
        Route::get('/status', [OnboardingController::class, 'getOnboardingStatus']);
        Route::post('/complete', [OnboardingController::class, 'completeOnboarding']);
    });
});
