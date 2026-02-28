<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\QuizAttempt;
use App\Models\QuizResponse;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends BaseController
{
    /**
     * Get student progress for the authenticated user
     * Enhanced with additional filters and more detailed metrics
     */
    public function getStudentProgress(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'topic_id' => 'nullable|string',
            'subtopic_id' => 'nullable|string',
            'mastery_level' => 'nullable|string',
            'question_format' => 'nullable|string|in:multiple_choice,subjective',
            'learning_style' => 'nullable|string',
            'include_response_stats' => 'nullable|boolean',
        ]);

        $query = StudentProgress::where('user_id', $user->id);

        if (!empty($validated['topic_id'])) {
            $query->where('topic_id', $validated['topic_id']);
        }

        if (!empty($validated['subtopic_id'])) {
            $query->where('subtopic_id', $validated['subtopic_id']);
        }

        if (!empty($validated['mastery_level'])) {
            $query->where('mastery_level', $validated['mastery_level']);
        }

        $progress = $query->orderBy('last_attempt_at', 'desc')->get();

        // If include_response_stats is true, add detailed response statistics
        if (!empty($validated['include_response_stats'])) {
            $progress = $progress->map(function ($item) use ($user, $validated) {
                $responseQuery = QuizResponse::where('user_id', $user->id)
                    ->where('topic_id', $item->topic_id);
                
                if ($item->subtopic_id) {
                    $responseQuery->where('subtopic_id', $item->subtopic_id);
                } else {
                    $responseQuery->whereNull('subtopic_id');
                }
                
                if ($item->mastery_level) {
                    $responseQuery->where('mastery_level', $item->mastery_level);
                }
                
                // Apply additional filters if provided
                if (!empty($validated['question_format'])) {
                    $responseQuery->where('question_format', $validated['question_format']);
                }
                
                if (!empty($validated['learning_style'])) {
                    $responseQuery->where('learning_style', $validated['learning_style']);
                }
                
                $responseStats = $responseQuery
                    ->select([
                        DB::raw('COUNT(*) as total_questions'),
                        DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as total_correct'),
                        DB::raw('AVG(CASE WHEN total_marks > 0 THEN (marks_obtained * 100.0 / total_marks) ELSE 0 END) as avg_score'),
                        DB::raw('SUM(time_spent_seconds) as total_time')
                    ])
                    ->first();
                
                // Add response stats to the progress item
                $itemArray = $item->toArray();
                $itemArray['response_stats'] = [
                    'total_questions' => (int)($responseStats->total_questions ?? 0),
                    'total_correct' => (int)($responseStats->total_correct ?? 0),
                    'accuracy' => ($responseStats->total_questions ?? 0) > 0 
                        ? round((($responseStats->total_correct ?? 0) / ($responseStats->total_questions ?? 1)) * 100, 2) 
                        : 0,
                    'average_score' => round((float)($responseStats->avg_score ?? 0), 2),
                    'total_time_seconds' => (int)($responseStats->total_time ?? 0),
                ];
                
                return $itemArray;
            });
        }

        return $this->sendResponse($progress, 'Student progress retrieved successfully');
    }

    /**
     * Get comprehensive analytics dashboard data
     */
    public function getDashboardAnalytics(Request $request)
    {
        $user = Auth::user();

        try {
            // Overall statistics from QuizAttempt
            $totalAttempts = QuizAttempt::where('user_id', $user->id)->count();
            $totalQuestions = QuizAttempt::where('user_id', $user->id)->sum('total_questions');
            $totalCorrect = QuizAttempt::where('user_id', $user->id)->sum('correct_answers');
            $avgScore = QuizAttempt::where('user_id', $user->id)->avg('score_percentage') ?? 0;
            $totalExpGained = QuizAttempt::where('user_id', $user->id)->sum('exp_gained') ?? 0;

            // Overall statistics from QuizResponse (more accurate question-level data)
            $responseStats = QuizResponse::where('user_id', $user->id)
                ->select([
                    DB::raw('COUNT(*) as total_questions'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as total_correct'),
                    DB::raw('AVG(CASE WHEN total_marks > 0 THEN (marks_obtained * 100.0 / total_marks) ELSE 0 END) as avg_score')
                ])
                ->first();

            // Recent attempts
            $recentAttempts = QuizAttempt::where('user_id', $user->id)
                ->orderBy('completed_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($attempt) {
                    return [
                        'id' => $attempt->id,
                        'topic_id' => $attempt->topic_id,
                        'subtopic_id' => $attempt->subtopic_id,
                        'question_format' => $attempt->question_format,
                        'score_percentage' => $attempt->score_percentage,
                        'exp_gained' => $attempt->exp_gained,
                        'completed_at' => $attempt->completed_at?->toISOString(),
                    ];
                });

            // Progress by topic (removed mastery_status)
            $topicProgress = StudentProgress::where('user_id', $user->id)
                ->select([
                    'topic_id',
                    DB::raw('SUM(total_attempts) as attempts'),
                    DB::raw('AVG(average_score) as avg_score'),
                    DB::raw('MAX(best_score) as best_score'),
                    DB::raw('SUM(total_questions_answered) as total_questions')
                ])
                ->groupBy('topic_id')
                ->get();

            // Performance by question format
            $performanceByFormat = QuizResponse::where('user_id', $user->id)
                ->select([
                    'question_format',
                    DB::raw('COUNT(*) as total_questions'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as total_correct'),
                    DB::raw('AVG(CASE WHEN total_marks > 0 THEN (marks_obtained * 100.0 / total_marks) ELSE 0 END) as avg_score')
                ])
                ->groupBy('question_format')
                ->get()
                ->map(function ($item) {
                    return [
                        'question_format' => $item->question_format,
                        'total_questions' => (int)$item->total_questions,
                        'total_correct' => (int)$item->total_correct,
                        'accuracy' => $item->total_questions > 0 
                            ? round(($item->total_correct / $item->total_questions) * 100, 2) 
                            : 0,
                        'average_score' => round((float)$item->avg_score, 2),
                    ];
                });

            // Performance by learning style
            $performanceByLearningStyle = QuizResponse::where('user_id', $user->id)
                ->whereNotNull('learning_style')
                ->select([
                    'learning_style',
                    DB::raw('COUNT(*) as total_questions'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as total_correct'),
                    DB::raw('AVG(CASE WHEN total_marks > 0 THEN (marks_obtained * 100.0 / total_marks) ELSE 0 END) as avg_score')
                ])
                ->groupBy('learning_style')
                ->get()
                ->map(function ($item) {
                    return [
                        'learning_style' => $item->learning_style,
                        'total_questions' => (int)$item->total_questions,
                        'total_correct' => (int)$item->total_correct,
                        'accuracy' => $item->total_questions > 0 
                            ? round(($item->total_correct / $item->total_questions) * 100, 2) 
                            : 0,
                        'average_score' => round((float)$item->avg_score, 2),
                    ];
                });

            // Performance over time (last 30 days)
            $performanceOverTime = QuizAttempt::where('user_id', $user->id)
                ->where('completed_at', '>=', now()->subDays(30))
                ->select([
                    DB::raw('DATE(completed_at) as date'),
                    DB::raw('AVG(score_percentage) as avg_score'),
                    DB::raw('COUNT(*) as attempts'),
                    DB::raw('SUM(exp_gained) as exp_gained')
                ])
                ->groupBy(DB::raw('DATE(completed_at)'))
                ->orderBy('date', 'asc')
                ->get();

            // Performance over time (last 7 days) for recent trends
            $recentPerformance = QuizAttempt::where('user_id', $user->id)
                ->where('completed_at', '>=', now()->subDays(7))
                ->select([
                    DB::raw('DATE(completed_at) as date'),
                    DB::raw('AVG(score_percentage) as avg_score'),
                    DB::raw('COUNT(*) as attempts'),
                    DB::raw('SUM(exp_gained) as exp_gained')
                ])
                ->groupBy(DB::raw('DATE(completed_at)'))
                ->orderBy('date', 'asc')
                ->get();

            // EXP analytics
            $currentExp = $user->exp ?? 0;
            $currentLevel = $user->level ?? 1;
            $totalExpForCurrentLevel = User::getTotalExpForLevel($currentLevel);
            $expInCurrentLevel = max(0, $currentExp - $totalExpForCurrentLevel);
            $expNeededForNextLevel = $currentLevel < 12 ? User::getExpNeededForLevel($currentLevel) : null;
            $totalExpForNextLevel = $currentLevel < 12 ? User::getTotalExpForLevel($currentLevel + 1) : null;
            $expProgress = $expNeededForNextLevel && $expNeededForNextLevel > 0 
                ? ($expInCurrentLevel / $expNeededForNextLevel) * 100 
                : 0;

            // EXP trends (last 30 days)
            $expTrends = QuizAttempt::where('user_id', $user->id)
                ->where('completed_at', '>=', now()->subDays(30))
                ->select([
                    DB::raw('DATE(completed_at) as date'),
                    DB::raw('SUM(exp_gained) as exp_gained')
                ])
                ->groupBy(DB::raw('DATE(completed_at)'))
                ->orderBy('date', 'asc')
                ->get();

            $dashboardData = [
                'overall' => [
                    'total_attempts' => $totalAttempts,
                    'total_questions' => $totalQuestions,
                    'total_correct' => $totalCorrect,
                    'average_score' => round($avgScore, 2),
                    'total_exp_gained' => $totalExpGained,
                    // More accurate stats from QuizResponse
                    'response_stats' => [
                        'total_questions' => (int)($responseStats->total_questions ?? 0),
                        'total_correct' => (int)($responseStats->total_correct ?? 0),
                        'average_score' => round((float)($responseStats->avg_score ?? 0), 2),
                    ],
                ],
                'exp_info' => [
                    'current_exp' => $currentExp,
                    'current_level' => $currentLevel,
                    'exp_in_current_level' => $expInCurrentLevel,
                    'exp_needed_for_next_level' => $expNeededForNextLevel,
                    'total_exp_for_next_level' => $totalExpForNextLevel,
                    'exp_progress' => round($expProgress, 2),
                    'total_exp_gained' => $totalExpGained,
                ],
                'recent_attempts' => $recentAttempts,
                'topic_progress' => $topicProgress,
                'performance_by_format' => $performanceByFormat,
                'performance_by_learning_style' => $performanceByLearningStyle,
                'performance_over_time' => $performanceOverTime,
                'recent_performance' => $recentPerformance,
                'exp_trends' => $expTrends,
            ];

            return $this->sendResponse($dashboardData, 'Dashboard analytics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard analytics: ' . $e->getMessage());
            return $this->sendError('Failed to fetch analytics', $e->getMessage(), 500);
        }
    }

    /**
     * Update or create student progress after a quiz attempt
     * This is typically called from MathQuestController after quiz submission
     * Uses QuizResponse data for more accurate question-level metrics
     */
    public function updateStudentProgress($userId, $topicId, $subtopicId, $masteryLevel, $attemptData)
    {
        try {
            DB::beginTransaction();

            $progress = StudentProgress::firstOrNew([
                'user_id' => $userId,
                'topic_id' => $topicId,
                'subtopic_id' => $subtopicId ?? null,
                'mastery_level' => $masteryLevel ?? null,
            ]);

            $previousAvgScore = $progress->average_score ?? 0;

            // Get all attempts for this progress record (for attempt-level metrics)
            $attempts = QuizAttempt::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->where(function ($query) use ($subtopicId) {
                    if ($subtopicId) {
                        $query->where('subtopic_id', $subtopicId);
                    } else {
                        $query->whereNull('subtopic_id');
                    }
                })
                ->when($masteryLevel, function ($query) use ($masteryLevel) {
                    $query->where('mastery_level', $masteryLevel);
                })
                ->get();

            // Get all responses for this progress record (for question-level metrics - more accurate)
            // Get response IDs from the matching attempts (since QuizResponse.mastery_level is per-question,
            // we need to match through quiz_attempt_id to get all responses from attempts with matching mastery_level)
            $attemptIds = $attempts->pluck('id')->toArray();
            
            $responsesQuery = QuizResponse::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->whereIn('quiz_attempt_id', $attemptIds) // Match responses through attempt IDs
                ->where(function ($query) use ($subtopicId) {
                    if ($subtopicId) {
                        $query->where('subtopic_id', $subtopicId);
                    } else {
                        $query->whereNull('subtopic_id');
                    }
                });

            $responses = $responsesQuery->get();

            // Calculate metrics from QuizResponse (more granular and accurate)
            $totalQuestionsAnswered = $responses->count();
            $totalCorrect = $responses->where('is_correct', true)->count();
            $totalIncorrect = $responses->where('is_correct', false)->count();
            $totalMarks = $responses->sum('total_marks');
            $marksObtained = $responses->sum('marks_obtained');
            $averageScore = $totalMarks > 0 ? ($marksObtained / $totalMarks) * 100 : 0;
            $totalTimeSpent = $responses->sum('time_spent_seconds') ?? 0;

            // Calculate best score from attempts (percentage-based, more meaningful for comparison)
            $attemptScores = $attempts->pluck('score_percentage')->filter()->toArray();
            $bestScore = !empty($attemptScores) ? max($attemptScores) : 0;

            // Set progress metrics
            $progress->total_attempts = $attempts->count();
            $progress->total_questions_answered = $totalQuestionsAnswered;
            $progress->total_correct = $totalCorrect;
            $progress->total_incorrect = $totalIncorrect;
            $progress->average_score = round($averageScore, 2);
            $progress->best_score = round($bestScore, 2);
            
            // Calculate improvement rate if we had a previous score
            if ($previousAvgScore > 0) {
                $progress->improvement_rate = $progress->calculateImprovementRate($previousAvgScore);
            }

            // Time metrics
            $progress->total_time_spent_seconds = $totalTimeSpent;
            $progress->average_time_per_question = $totalQuestionsAnswered > 0
                ? round($totalTimeSpent / $totalQuestionsAnswered, 2)
                : 0;

            // Update timestamps
            if (!$progress->first_attempt_at && $attempts->count() > 0) {
                $progress->first_attempt_at = $attempts->min('started_at');
            }
            $progress->last_attempt_at = $attempts->max('completed_at') ?? now();

            $progress->save();

            DB::commit();

            Log::info("Updated student progress for user {$userId}, topic {$topicId}, subtopic {$subtopicId}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating student progress: ' . $e->getMessage());
            throw $e; // Re-throw to allow caller to handle if needed
        }
    }

}

