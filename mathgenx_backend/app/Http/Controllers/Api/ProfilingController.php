<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\MathAnswer;
use App\Models\MathQuestion;
use App\Models\MathResponse;
use App\Models\MathResult;
use App\Models\User;
use App\Models\VarkAnswer;
use App\Models\VarkQuestion;
use App\Models\VarkResponse;
use App\Models\VarkResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfilingController extends BaseController
{
    public function getVarkQuestions()
    {
        $data = VarkQuestion::with('answers')->orderBy('order_number')->get();
        return $this->sendResponse($data, 'VARK questions retrieved successfully.');
    }

    public function getMathQuestions()
    {
        $data = MathQuestion::with('answers')->inRandomOrder()->limit(20)->get();
        return $this->sendResponse($data, 'Math questions retrieved successfully.');
    }

    public function getUserProfilingResponses()
    {
        $user = Auth::user();
        $userId = $user->id;

        // Get VARK responses with questions and answers
        $varkResponses = VarkResponse::where('user_id', $userId)
            ->with(['question', 'answer'])
            ->orderBy('question_id')
            ->get()
            ->map(function ($response) {
                return [
                    'id' => $response->id,
                    'question_id' => $response->question_id,
                    'question_text' => $response->question->question_text ?? '',
                    'answer_id' => $response->answer_id,
                    'answer_text' => $response->answer->answer_text ?? '',
                    'category' => $response->answer->category ?? '',
                ];
            });

        // Get Math responses with questions and answers
        $mathResponses = MathResponse::where('user_id', $userId)
            ->with(['question', 'answer'])
            ->orderBy('question_id')
            ->get()
            ->map(function ($response) {
                return [
                    'id' => $response->id,
                    'question_id' => $response->question_id,
                    'question_text' => $response->question->question_text ?? '',
                    'answer_id' => $response->answer_id,
                    'answer_text' => $response->answer->answer_text ?? '',
                    'is_correct' => $response->is_correct,
                ];
            });

        // Get VARK result summary
        $varkResult = VarkResult::where('user_id', $userId)->first();
        $varkSummary = $varkResult ? [
            'dominant_style' => $varkResult->dominant_style,
            'score_v' => $varkResult->score_v,
            'score_a' => $varkResult->score_a,
            'score_r' => $varkResult->score_r,
            'score_k' => $varkResult->score_k,
        ] : null;

        // Get Math result summary
        $mathResult = MathResult::where('user_id', $userId)->first();
        $mathSummary = $mathResult ? [
            'level' => $mathResult->level,
            'total_score' => $mathResult->total_score,
        ] : null;

        return $this->sendResponse([
            'vark' => [
                'responses' => $varkResponses,
                'summary' => $varkSummary,
            ],
            'math' => [
                'responses' => $mathResponses,
                'summary' => $mathSummary,
            ],
        ], 'Profiling responses retrieved successfully.');
    }

    public function submitAll(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $varkResponses = $request->input('vark', []);
        $mathResponses = $request->input('math', []);
        $profileData = $request->input('profile', []);

        if (empty($varkResponses) || empty($mathResponses)) {
            return $this->sendError('Both VARK and Math responses are required.');
        }

        DB::beginTransaction();

        try {
            // --- Process VARK responses ---
            [$varkScores, $dominant] = $this->processVark($varkResponses, $userId);

            // --- Process Math responses ---
            [$totalScore, $totalQuestions, $mathLevel] = $this->processMath($mathResponses, $userId);

            // --- Mark profiling as completed ---
            $user->update(['has_completed_profiling' => true]);

            DB::commit();

            return $this->sendResponse([
                'vark' => [
                    'scores' => $varkScores,
                    'dominant' => $dominant,
                ],
                'math' => [
                    'total_score' => $totalScore,
                    'total_questions' => $totalQuestions,
                    'level' => $mathLevel,
                ],
                'completed' => true,
            ], 'Profiling submitted successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Failed to submit profiling.', $e->getMessage());
        }
    }

    /**
     * Process VARK responses (logic unchanged)
     */
    private function processVark(array $responses, $userId)
    {
        foreach ($responses as $r) {
            VarkResponse::updateOrCreate(
                [
                    'user_id' => $userId,
                    'question_id' => $r['question_id'],
                ],
                [
                    'answer_id' => $r['answer_id'],
                ]
            );
        }

        $scores = ['V' => 0, 'A' => 0, 'R' => 0, 'K' => 0];
        $answerIds = array_column($responses, 'answer_id');
        $answers = VarkAnswer::whereIn('id', $answerIds)->get(['id', 'category']);

        foreach ($answers as $answer) {
            $category = strtoupper($answer->category);
            if (isset($scores[$category])) {
                $scores[$category]++;
            }
        }

        $max = max($scores);
        $dominants = array_keys(array_filter($scores, fn($v) => $v === $max));
        $dominantCode = count($dominants) === 1 ? $dominants[0] : 'Multimodal';

        // Map single-letter codes to full words
        $mapping = [
            'V' => 'Visual',
            'A' => 'Auditory',
            'R' => 'Read',
            'K' => 'Kinesthetic',
            'Multimodal' => 'Multimodal',
        ];
        $dominant = $mapping[$dominantCode] ?? $dominantCode;

        VarkResult::updateOrCreate(
            ['user_id' => $userId],
            [
                'score_v' => $scores['V'],
                'score_a' => $scores['A'],
                'score_r' => $scores['R'],
                'score_k' => $scores['K'],
                'dominant_style' => $dominant,
            ]
        );

        return [$scores, $dominant];
    }

    /**
     * Process Math responses (logic unchanged)
     */
    private function processMath(array $responses, $userId)
    {
        $correctCount = 0;

        foreach ($responses as $r) {
            $question = MathQuestion::with('answers')->find($r['question_id']);
            if ($question) {
                $isCorrect = $question->answers->where('id', $r['answer_id'])->first()?->is_correct ?? false;
                if ($isCorrect) {
                    $correctCount++;
                }

                // Save each response to MathResponse table
                MathResponse::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'question_id' => $r['question_id'],
                    ],
                    [
                        'answer_id' => $r['answer_id'],
                        'is_correct' => $isCorrect,
                    ]
                );
            }
        }

        $totalQuestions = count($responses);
        $scorePercentage = ($correctCount / $totalQuestions) * 100;
        $initialLevel = $this->determineInitialLevel($scorePercentage);

        // Update user's level and exp in users table
        $user = User::find($userId);
        if ($user) {
            // If user is advanced (level 2), set exp to 100 (exp needed to reach level 2 from level 1)
            // If user is beginner (level 1), set exp to 0 (starting point)
            $initialExp = $initialLevel === 2 ? 100 : 0;
            
            $user->update([
                'level' => $initialLevel,
                'exp' => $initialExp,
            ]);
        }

        // Store level as string for backward compatibility with MathResult table
        $levelString = $initialLevel === 1 ? 'Beginner' : 'Advanced';
        
        MathResult::updateOrCreate(
            ['user_id' => $userId],
            [
                'total_score' => $correctCount,
                'level' => $levelString,
            ]
        );

        return [$correctCount, $totalQuestions, $levelString];
    }

    /**
     * Determine initial level based on score percentage
     * Score <= 70% = Beginner (level 1)
     * Score > 70% = Advanced (level 2)
     */
    private function determineInitialLevel($scorePercentage)
    {
        return $scorePercentage > 70 ? 2 : 1;
    }
}