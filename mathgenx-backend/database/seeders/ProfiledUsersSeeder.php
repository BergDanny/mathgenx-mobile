<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VarkQuestion;
use App\Models\VarkAnswer;
use App\Models\VarkResponse;
use App\Models\VarkResult;
use App\Models\MathQuestion;
use App\Models\MathAnswer;
use App\Models\MathResponse;
use App\Models\MathResult;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfiledUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $varkStyles = [
            'Visual' => 'visual@mathgenx.test',
            'Auditory' => 'auditory@mathgenx.test',
            'Read' => 'reading@mathgenx.test',
            'Kinesthetic' => 'kinesthetic@mathgenx.test',
        ];

        foreach ($varkStyles as $style => $email) {
            DB::transaction(function () use ($style, $email) {
                // Create or get user
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => ucfirst($style) . ' User',
                        'password' => bcrypt('password'),
                        'has_completed_profiling' => true,
                        'level' => 1,
                        'exp' => 0,
                    ]
                );
                $user->assignRole('learner');

                // Get all VARK questions
                $varkQuestions = VarkQuestion::orderBy('order_number')->get();
                
                // Get all VARK answers grouped by question
                $varkAnswersByQuestion = VarkAnswer::all()->groupBy('question_id');
                
                // Create VARK responses - select answers that favor the user's style
                $scores = ['V' => 0, 'A' => 0, 'R' => 0, 'K' => 0];
                $styleCode = $this->getStyleCode($style);
                
                foreach ($varkQuestions as $question) {
                    $answers = $varkAnswersByQuestion[$question->id] ?? collect();
                    
                    // Find answer with matching style category
                    $preferredAnswer = $answers->where('category', $styleCode)->first();
                    
                    // If no exact match, try to get one that favors this style
                    if (!$preferredAnswer) {
                        $preferredAnswer = $answers->first();
                    }
                    
                    if ($preferredAnswer) {
                        VarkResponse::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'question_id' => $question->id,
                            ],
                            [
                                'answer_id' => $preferredAnswer->id,
                            ]
                        );
                        
                        $category = strtoupper($preferredAnswer->category);
                        if (isset($scores[$category])) {
                            $scores[$category]++;
                        }
                    }
                }
                
                // Ensure the user's style is dominant by adjusting scores if needed
                // If their style isn't highest, boost it
                $maxScore = max($scores);
                if ($scores[$styleCode] < $maxScore) {
                    $scores[$styleCode] = $maxScore + 2;
                }
                
                // Create VARK result
                $dominantMapping = [
                    'V' => 'Visual',
                    'A' => 'Auditory',
                    'R' => 'Read',
                    'K' => 'Kinesthetic',
                ];
                
                $maxScore = max($scores);
                $dominantCodes = array_keys(array_filter($scores, fn($v) => $v === $maxScore));
                $dominant = count($dominantCodes) === 1 
                    ? ($dominantMapping[$dominantCodes[0]] ?? $style)
                    : $style;
                
                VarkResult::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'score_v' => $scores['V'],
                        'score_a' => $scores['A'],
                        'score_r' => $scores['R'],
                        'score_k' => $scores['K'],
                        'dominant_style' => $dominant,
                    ]
                );
                
                // Get all Math questions
                $mathQuestions = MathQuestion::with('answers')->get();
                
                // Create Math responses - mix of correct and incorrect
                // Visual: 8 correct, 2 wrong (80%)
                // Auditory: 7 correct, 3 wrong (70%)
                // Reading: 6 correct, 4 wrong (60%)
                // Kinesthetic: 9 correct, 1 wrong (90%)
                $correctCounts = [
                    'Visual' => 8,
                    'Auditory' => 7,
                    'Read' => 6,
                    'Kinesthetic' => 9,
                ];
                
                $correctCount = $correctCounts[$style] ?? 7;
                $totalQuestions = min(10, $mathQuestions->count());
                
                if ($totalQuestions === 0) {
                    return; // Skip if no math questions available
                }
                
                $correctAnswered = 0;
                $wrongAnswered = 0;
                
                foreach ($mathQuestions->take($totalQuestions) as $index => $question) {
                    $correctAnswer = $question->answers->where('is_correct', true)->first();
                    $wrongAnswers = $question->answers->where('is_correct', false);
                    
                    if ($correctAnswer && $wrongAnswers->isNotEmpty()) {
                        // Decide if this should be correct or wrong
                        $shouldBeCorrect = $correctAnswered < $correctCount;
                        
                        if ($shouldBeCorrect) {
                            $selectedAnswer = $correctAnswer;
                            $isCorrect = true;
                            $correctAnswered++;
                        } else {
                            $selectedAnswer = $wrongAnswers->random();
                            $isCorrect = false;
                            $wrongAnswered++;
                        }
                        
                        MathResponse::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'question_id' => $question->id,
                            ],
                            [
                                'answer_id' => $selectedAnswer->id,
                                'is_correct' => $isCorrect,
                            ]
                        );
                    }
                }
                
                // Calculate math level (prevent division by zero)
                $scorePercentage = $totalQuestions > 0 ? ($correctAnswered / $totalQuestions) * 100 : 0;
                $level = $scorePercentage > 70 ? 'Advanced' : 'Beginner';
                $userLevel = $scorePercentage > 70 ? 2 : 1;
                $userExp = $userLevel === 2 ? 100 : 0;
                
                // Update user level and exp
                $user->update([
                    'level' => $userLevel,
                    'exp' => $userExp,
                ]);
                
                // Create Math result
                MathResult::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'total_score' => $correctAnswered,
                        'level' => $level,
                    ]
                );
            });
        }
    }
    
    /**
     * Get style code from style name
     */
    private function getStyleCode($style): string
    {
        $mapping = [
            'Visual' => 'V',
            'Auditory' => 'A',
            'Read' => 'R',
            'Reading' => 'R',
            'Kinesthetic' => 'K',
        ];
        
        return $mapping[$style] ?? 'V';
    }
}