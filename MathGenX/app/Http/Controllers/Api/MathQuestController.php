<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Models\QuestionBank;
use App\Models\QuizAttempt;
use App\Models\QuizResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class MathQuestController extends BaseController
{
    private function getRagApiUrl(): string
    {
        return config('services.rag.url');
    }

    private function getRagApiTimeout(): int
    {
        return config('services.rag.timeout');
    }

    /**
     * Format multiple choice question
     */
    private function formatMultipleChoiceQuestion($questionData)
    {
        $answerKey = $questionData['answer_key'] ?? null;

        return [
            'id' => $questionData['id'] ?? 1,
            'question_text' => $questionData['question_text'] ?? '',
            'level' => $questionData['level'] ?? $questionData['mastery_level'] ?? 1,
            'learning_style' => $questionData['learning_style'] ?? 'Visual',
            'question_format' => 'multiple_choice',
            'topic_id' => $questionData['topic_id'] ?? null,
            'image_url' => $questionData['image_url'] ?? null,
            'choices' => array_map(function ($choice) use ($answerKey) {
                return [
                    'id' => $choice['id'],
                    'label' => $choice['label'],
                    'answer_text' => $choice['text'],
                    'is_correct' => $choice['label'] === $answerKey
                ];
            }, $questionData['choices'] ?? [])
        ];
    }

    /**
     * Format subjective question
     */
    private function formatSubjectiveQuestion($questionData)
    {
        return [
            'id' => $questionData['id'] ?? 1,
            'question_text' => $questionData['question_text'] ?? '',
            'level' => $questionData['level'] ?? $questionData['mastery_level'] ?? 1,
            'learning_style' => $questionData['learning_style'] ?? 'Visual',
            'question_format' => $questionData['question_format'] ?? 'subjective',
            'topic_id' => $questionData['topic_id'] ?? null,
            'subtopic_id' => $questionData['subtopic_id'] ?? null,
            'image_url' => $questionData['image_url'] ?? null,
            'working_steps' => $questionData['working_steps'] ?? [],
            'final_answer' => $questionData['final_answer'] ?? '',
            'answer_type' => $questionData['answer_type'] ?? 'text',
            'accepted_variations' => $questionData['accepted_variations'] ?? [],
            'total_marks' => $questionData['total_marks'] ?? 1
        ];
    }

    /**
     * Format question based on type
     */
    private function formatQuestion($questionData, $questionFormat)
    {
        switch ($questionFormat) {
            case 'multiple_choice':
                return $this->formatMultipleChoiceQuestion($questionData);
            case 'subjective':
                return $this->formatSubjectiveQuestion($questionData);
            default:
                return $this->formatMultipleChoiceQuestion($questionData);
        }
    }

    /**
     * Get IDs of questions the user has previously attempted for a given topic/subtopic
     * Extracts question IDs from quiz_parameters['questions'] array
     */
    private function getPreviouslyAttemptedQuestionIds($userId, $topicId, $subtopicId)
    {
        try {
            $attempts = QuizAttempt::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->where('subtopic_id', $subtopicId)
                ->get();

            $questionIds = [];
            foreach ($attempts as $attempt) {
                $quizParameters = $attempt->quiz_parameters;
                if (isset($quizParameters['questions']) && is_array($quizParameters['questions'])) {
                    foreach ($quizParameters['questions'] as $question) {
                        if (isset($question['id'])) {
                            $questionIds[] = $question['id'];
                        }
                    }
                }
            }

            return array_unique($questionIds);
        } catch (\Exception $e) {
            Log::error('Error getting previously attempted question IDs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse mastery level string to array of individual levels
     * Handles formats like "TP1", "TP1&TP2", "TP3&TP4"
     */
    private function parseMasteryLevel($masteryLevel)
    {
        if (strpos($masteryLevel, '&') !== false) {
            // Split combined format like "TP1&TP2" into ["TP1", "TP2"]
            return array_map('trim', explode('&', $masteryLevel));
        }

        // Single level
        return [$masteryLevel];
    }

    /**
     * Format database questions to match API response format
     */
    private function formatDatabaseQuestions($questions, $questionFormat, $level = 1)
    {
        return $questions->map(function ($question) use ($questionFormat, $level) {
            $questionData = [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'level' => $level, // Use provided level
                'learning_style' => $question->learning_style,
                'question_format' => $question->question_format,
                'topic_id' => $question->topic_id,
                'subtopic_id' => $question->subtopic_id,
            ];
            
            // Convert database format to API-like format
            if ($questionFormat === 'multiple_choice' && $question->choices) {
                $choices = $question->choices; // Already cast to array by model
                if (!is_array($choices)) {
                    $choices = json_decode($choices, true) ?? [];
                }
                
                $questionData['choices'] = array_map(function ($choice, $index) {
                    // Handle both formats: ['label' => 'A', 'text' => '...'] or ['answer_text' => '...']
                    return [
                        'id' => $index + 1,
                        'label' => $choice['label'] ?? chr(65 + $index), // A, B, C, D
                        'text' => $choice['text'] ?? $choice['answer_text'] ?? ''
                    ];
                }, $choices, array_keys($choices));
                $questionData['answer_key'] = $question->answer_key;
            }

            if ($questionFormat === 'subjective') {
                $questionData['working_steps'] = $question->working_steps ?? []; // Already cast to array
                $questionData['final_answer'] = $question->final_answer ?? '';
                $questionData['answer_type'] = $question->answer_type ?? 'text';
                $questionData['accepted_variations'] = $question->accepted_variations ?? [];
                $questionData['total_marks'] = $question->total_marks ?? 1;
            }

            return $questionData;
        })->toArray();
    }

    public function generateQuestion(Request $request)
    {
        Log::info('MathQuest Generate Question Request', [
            'all' => $request->all(),
            'topic' => $request->input('topic'),
            'subtopic' => $request->input('subtopic'),
            'question_format' => $request->input('question_format'),
            'level' => $request->input('level'),
            'vark_style' => $request->input('vark_style')
        ]);

        // Validate incoming request
        $validated = $request->validate([
            'topic' => 'required|string|between:1,10',
            'subtopic' => 'nullable|string', // Made optional as per user's note
            'question_format' => 'nullable|string|in:multiple_choice,subjective',
            'language' => 'nullable|string|in:english,malay',
            'num_questions' => 'nullable|integer|min:1|max:50', // Optional - Python will handle defaults if not provided
            'level' => 'nullable|integer|min:1|max:12',
            'vark_style' => 'nullable|string',
        ]);

        $validated['question_format'] = $validated['question_format'] ?? 'multiple_choice';
        $validated['language'] = $validated['language'] ?? 'english';

        $user = $request->user();

        // Fallback to default values if user preferences are not set
        $userVark = optional($user->varkResult ?? null)->dominant_style ?? null;
        $userLevel = $user->level ?? 1; // Get user's level from users table (1-12)

        $rawVark = $validated['vark_style'] ?? $request->input('vark_style') ?? $userVark ?? 'Visual';
        $validated['vark_style'] = $rawVark;
        $validated['level'] = $validated['level'] ?? $request->input('level') ?? $userLevel;

        // Extract parameters for database lookup
        $topicId = $validated['topic'];
        $subtopicId = $validated['subtopic'];
        $questionFormat = $validated['question_format'];
        $level = $validated['level'];
        $learningStyle = $validated['vark_style'];

        // Keep topic_id and subtopic_id as-is (e.g., "1" and "1.1") - no normalization

        // For database queries, use provided num_questions or default based on question format
        // Python will handle defaults if not sent in API request
        $numQuestions = $validated['num_questions'] ?? ($questionFormat === 'multiple_choice' ? 10 : 5);

        // Log the parameters being used
        Log::info('generateQuestion called with parameters:', [
            'topic' => $topicId,
            'subtopic' => $subtopicId,
            'question_format' => $questionFormat,
            'level' => $level,
            'learning_style' => $learningStyle,
            'language' => $validated['language'],
            'num_questions' => $numQuestions
        ]);

        // STEP 1: Call RAG API first to generate new questions
        Log::info('Calling RAG API to generate new questions');

        try {
            // Prepare API payload - only include num_questions if it was provided
            $apiPayload = $validated;
            if (!isset($validated['num_questions'])) {
                // Remove num_questions from payload so Python handles defaults
                unset($apiPayload['num_questions']);
            }
            
            $response = Http::timeout($this->getRagApiTimeout())
                ->post($this->getRagApiUrl(), $apiPayload);

            Log::info('Response Status: ' . $response->status());
            Log::info('Response Body: ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();

                // Save to database - pass language from validated request
                $savedQuestions = $this->saveQuestionsToDatabase($data, $validated['language']);

                // Extract questions array from API response
                $apiQuestions = $data['data']['questions'] ?? [];

                // Transform each question based on its type
                $formattedQuestions = array_map(function ($questionData) use ($questionFormat) {
                    return $this->formatQuestion($questionData, $questionFormat);
                }, $apiQuestions);

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'questions' => $formattedQuestions,
                        'total_requested' => $data['data']['total_requested'] ?? count($formattedQuestions),
                        'question_format' => $questionFormat,
                        'source' => 'api',
                        'saved_to_db' => count($savedQuestions)
                    ]
                ]);
            }

            // STEP 2: API failed (non-2xx status), try to get questions from database as fallback
            Log::warning('API call failed with status ' . $response->status() . ', attempting to retrieve questions from database as fallback');

            $fallbackQuestions = $this->getFallbackQuestionsFromDatabase(
                $topicId,
                $subtopicId,
                $questionFormat,
                $level,
                $learningStyle,
                $numQuestions,
                $user->id,
                $validated['language']
            );

            if ($fallbackQuestions !== null && $fallbackQuestions->count() > 0) {
                $formattedFallbackQuestions = $this->formatDatabaseQuestions($fallbackQuestions, $questionFormat, $level);
                $formattedQuestions = array_map(function ($questionData) use ($questionFormat) {
                    return $this->formatQuestion($questionData, $questionFormat);
                }, $formattedFallbackQuestions);

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'questions' => $formattedQuestions,
                        'total_requested' => $numQuestions,
                        'question_format' => $questionFormat,
                        'source' => 'database_fallback',
                        'saved_to_db' => 0,
                        'warning' => 'API failed, returned questions from database'
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate questions from API and no matching questions found in database',
                'can_retry' => true,
                'details' => $response->json()
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('API Exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // STEP 3: API exception (connection failed, timeout, etc), try database fallback
            Log::warning('API exception occurred, attempting database fallback');

            $fallbackQuestions = $this->getFallbackQuestionsFromDatabase(
                $topicId,
                $subtopicId,
                $questionFormat,
                $level,
                $learningStyle,
                $numQuestions,
                $user->id,
                $validated['language']
            );

            if ($fallbackQuestions !== null && $fallbackQuestions->count() > 0) {
                $formattedFallbackQuestions = $this->formatDatabaseQuestions($fallbackQuestions, $questionFormat, $level);
                $formattedQuestions = array_map(function ($questionData) use ($questionFormat) {
                    return $this->formatQuestion($questionData, $questionFormat);
                }, $formattedFallbackQuestions);

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'questions' => $formattedQuestions,
                        'total_requested' => $numQuestions,
                        'question_format' => $questionFormat,
                        'source' => 'database_fallback',
                        'saved_to_db' => 0,
                        'warning' => 'API connection failed, returned questions from database'
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'API connection failed and no matching questions found in database',
                'can_retry' => true,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fallback questions from database with progressive relaxation
     * Tries multiple query strategies in order of preference
     * Randomizes question order and excludes previously attempted questions
     */
    private function getFallbackQuestionsFromDatabase($topicId, $subtopicId, $questionFormat, $level, $learningStyle, $numQuestions, $userId = null, $language = 'english')
    {
        try {
            // Get previously attempted question IDs for this user to avoid repetition
            $excludeQuestionIds = [];
            if ($userId) {
                $excludeQuestionIds = $this->getPreviouslyAttemptedQuestionIds($userId, $topicId, $subtopicId);
                if (!empty($excludeQuestionIds)) {
                    Log::info("Fallback: Excluding " . count($excludeQuestionIds) . " previously attempted questions");
                }
            }

            // Strategy 1: Match all parameters including learning style and language
            $query = QuestionBank::where('topic_id', $topicId)
                ->where('subtopic_id', $subtopicId)
                ->where('question_format', $questionFormat)
                ->where('learning_style', $learningStyle)
                ->where('language', $language);

            if (!empty($excludeQuestionIds)) {
                $query->whereNotIn('id', $excludeQuestionIds);
            }

            $questions = $query->inRandomOrder()->limit($numQuestions)->get();
            Log::info("Fallback Strategy 1 result: {$questions->count()} questions found");
            if ($questions->count() > 0) {
                Log::info("Fallback Strategy 1: Found {$questions->count()} questions matching all parameters");
                return $questions;
            }

            // Strategy 2: Ignore learning style but keep language
            $query = QuestionBank::where('topic_id', $topicId)
                ->where('subtopic_id', $subtopicId)
                ->where('question_format', $questionFormat)
                ->where('language', $language);

            if (!empty($excludeQuestionIds)) {
                $query->whereNotIn('id', $excludeQuestionIds);
            }

            $questions = $query->inRandomOrder()->limit($numQuestions)->get();
            if ($questions->count() > 0) {
                Log::info("Fallback Strategy 2: Found {$questions->count()} questions (ignoring learning style)");
                return $questions;
            }

            // Strategy 3: Ignore learning style but keep language
            $query = QuestionBank::where('topic_id', $topicId)
                ->where('subtopic_id', $subtopicId)
                ->where('question_format', $questionFormat)
                ->where('language', $language);

            if (!empty($excludeQuestionIds)) {
                $query->whereNotIn('id', $excludeQuestionIds);
            }

            $questions = $query->inRandomOrder()->limit($numQuestions)->get();
            if ($questions->count() > 0) {
                Log::info("Fallback Strategy 3: Found {$questions->count()} questions (ignoring learning style)");
                return $questions;
            }

            // Strategy 4: Match topic, format, and language only (most relaxed)
            $query = QuestionBank::where('topic_id', $topicId)
                ->where('question_format', $questionFormat)
                ->where('language', $language);

            if (!empty($excludeQuestionIds)) {
                $query->whereNotIn('id', $excludeQuestionIds);
            }

            $questions = $query->inRandomOrder()->limit($numQuestions)->get();
            if ($questions->count() > 0) {
                Log::info("Fallback Strategy 4: Found {$questions->count()} questions (ignoring subtopic and learning style)");
                return $questions;
            }

            Log::warning("No fallback questions found in database for topic: {$topicId}");
            return null;
        } catch (\Exception $e) {
            Log::error('Error in getFallbackQuestionsFromDatabase: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalize topic_id from "Topic1" format back to "1" format
     */
    private function normalizeTopicIdToNumeric($topicId)
    {
        if (empty($topicId)) {
            return $topicId;
        }
        
        // If it's in "Topic1" format, convert to "1"
        if (preg_match('/^Topic(\d+)$/i', $topicId, $matches)) {
            return $matches[1];
        }
        
        // Otherwise return as-is
        return $topicId;
    }

    /**
     * Save API response questions to database
     */
    private function saveQuestionsToDatabase($apiResponse, $language = 'english')
    {
        $savedQuestions = [];

        try {
            $questions = $apiResponse['data']['questions'] ?? [];

            foreach ($questions as $questionData) {
                // Normalize topic_id from "Topic1" to "1" if needed
                $normalizedTopicId = $this->normalizeTopicIdToNumeric($questionData['topic_id'] ?? null);
                
                // Check if question already exists to avoid duplicates
                $existingQuestion = QuestionBank::where('topic_id', $normalizedTopicId)
                    ->where('subtopic_id', $questionData['subtopic_id'] ?? null)
                    ->where('question_text', $questionData['question_text'] ?? '')
                    ->first();

                if (!$existingQuestion) {
                    $questionFormat = $questionData['question_format'] ?? 'multiple_choice';

                    // Handle level from API (convert to string for database compatibility)
                    // API now sends 'level' (1-12), but database still uses 'mastery_level' string
                    $masteryLevel = $questionData['level'] ?? $questionData['mastery_level'] ?? '1';
                    if (is_numeric($masteryLevel)) {
                        $masteryLevel = (string)$masteryLevel;
                    }

                    // Use language from request parameter if provided, otherwise try from question data, otherwise default to 'english'
                    $questionLanguage = $language ?? $questionData['language'] ?? 'english';

                    $questionBank = QuestionBank::create([
                        'question_text' => $questionData['question_text'] ?? '',
                        'question_format' => $questionFormat,
                        'topic_id' => $normalizedTopicId,
                        'subtopic_id' => $questionData['subtopic_id'] ?? null,
                        'mastery_level' => $masteryLevel,
                        'learning_style' => $questionData['learning_style'] ?? 'Visual',
                        'language' => $questionLanguage,

                        // MCQ specific fields
                        'choices' => $questionFormat === 'multiple_choice' ? ($questionData['choices'] ?? null) : null,
                        'answer_key' => $questionFormat === 'multiple_choice' ? ($questionData['answer_key'] ?? null) : null,

                        // Subjective specific fields
                        'working_steps' => $questionFormat === 'subjective' ? ($questionData['working_steps'] ?? null) : null,
                        'final_answer' => $questionFormat === 'subjective' ? ($questionData['final_answer'] ?? null) : null,
                        'answer_type' => $questionFormat === 'subjective' ? ($questionData['answer_type'] ?? null) : null,
                        'accepted_variations' => $questionFormat === 'subjective' ? ($questionData['accepted_variations'] ?? null) : null,
                        'total_marks' => $questionFormat === 'subjective' ? ($questionData['total_marks'] ?? null) : null,

                        // Store complete API response for backup
                        'full_api_response' => $apiResponse
                    ]);

                    $savedQuestions[] = $questionBank;
                    Log::info("Saved question ID: {$questionBank->id} to database");
                }
            }
        } catch (\Exception $e) {
            Log::error('Error saving questions to database: ' . $e->getMessage());
        }

        return $savedQuestions;
    }

    /**
     * Save individual question (public method for manual saves)
     */
    public function saveQuestion(Request $request)
    {
        $validated = $request->validate([
            // Common fields (required for both types)
            'question_text' => 'required|string',
            'question_format' => 'required|in:multiple_choice,subjective',
            'topic_id' => 'required|string',
            'subtopic_id' => 'nullable|string',
            'mastery_level' => 'required|string',
            'learning_style' => 'required|string',
            // MCQ specific fields (nullable - only required for MCQ)
            'choices' => 'nullable|array',
            'answer_key' => 'nullable|string',
            // Subjective specific fields (nullable - only required for subjective)
            'working_steps' => 'nullable|array',
            'final_answer' => 'nullable|string',
            'answer_type' => 'nullable|string',
            'accepted_variations' => 'nullable|array',
            'total_marks' => 'nullable|integer',

            // Store complete API response for backup
            'full_api_response' => 'nullable|array'
        ]);

        try {
            $question = QuestionBank::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Question saved successfully',
                'data' => $question
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving question: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get questions from database (fallback when API fails)
     */
    public function getQuestionsFromDatabase(Request $request)
    {
        $validated = $request->validate([
            'topic_id' => 'required|string',
            'subtopic_id' => 'nullable|string',
            'question_format' => 'nullable|in:multiple_choice,subjective',
            'mastery_level' => 'nullable|string',
            'learning_style' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $query = QuestionBank::where('topic_id', $validated['topic_id']);

            if (!empty($validated['subtopic_id'])) {
                $query->where('subtopic_id', $validated['subtopic_id']);
            }

            if (!empty($validated['question_format'])) {
                $query->where('question_format', $validated['question_format']);
            }

            if (!empty($validated['mastery_level'])) {
                $query->where('mastery_level', $validated['mastery_level']);
            }

            if (!empty($validated['learning_style'])) {
                $query->where('learning_style', $validated['learning_style']);
            }

            $questions = $query->limit($validated['limit'] ?? 10)->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'questions' => $questions,
                    'total_found' => $questions->count(),
                    'source' => 'database'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving questions from database: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit quiz attempt with responses
     */
    public function submitAttempt(Request $request)
    {
        $validated = $request->validate([
            'topic_id' => 'required|string',
            'subtopic_id' => 'nullable|string',
            'question_format' => 'required|string|in:multiple_choice,subjective',
            'language' => 'nullable|string|in:english,malay',
            'level' => 'nullable|integer|min:1|max:12',
            'learning_style' => 'nullable|string',
            'questions' => 'required|array',
            'responses' => 'required|array',
            'started_at' => 'nullable|date',
        ]);

            $user = Auth::user();
            $currentLevel = $user->level ?? 1;

        DB::beginTransaction();
        try {
            // Calculate results
            $questions = $validated['questions'];
            $responses = $validated['responses'];
            $correctCount = 0;
            $incorrectCount = 0;
            $totalMarks = 0;
            $marksObtained = 0;
            $startedAt = $validated['started_at'] ? new \DateTime($validated['started_at']) : now();
            $completedAt = now();
            $timeSpent = $completedAt->diff($startedAt)->s;

            // Get level from request or use user's current level
            $quizLevel = $validated['level'] ?? $currentLevel;

            // Create quiz attempt
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'topic_id' => $validated['topic_id'],
                'subtopic_id' => $validated['subtopic_id'] ?? null,
                'question_format' => $validated['question_format'],
                'language' => $validated['language'] ?? 'english',
                'mastery_level' => (string)$quizLevel, // Store as string for backward compatibility
                'learning_style' => $validated['learning_style'] ?? null,
                'total_questions' => count($questions),
                'correct_answers' => 0, // Will update after processing
                'incorrect_answers' => 0, // Will update after processing
                'score_percentage' => 0, // Will update after processing
                'total_marks' => 0, // Will update after processing
                'marks_obtained' => 0, // Will update after processing
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'time_spent_seconds' => $timeSpent,
                'quiz_parameters' => [
                    'topic_id' => $validated['topic_id'],
                    'subtopic_id' => $validated['subtopic_id'] ?? null,
                    'question_format' => $validated['question_format'],
                    'language' => $validated['language'] ?? 'english',
                    'level' => $quizLevel,
                    'learning_style' => $validated['learning_style'] ?? null,
                    'questions' => $questions, // Store full question data for reference
                ],
                'source' => 'mathquest',
            ]);

            // Process each question and response
            $responsesCreated = 0;
            $responsesSkipped = 0;
            
            foreach ($questions as $index => $questionData) {
                $questionId = $questionData['id'] ?? null;
                if (!$questionId) {
                    $responsesSkipped++;
                    continue;
                }

                $userAnswer = $responses[$questionId] ?? null;
                $isCorrect = false;
                $correctAnswer = null;
                $marksForQuestion = 0;
                $totalMarksForQuestion = $questionData['total_marks'] ?? 1;

                // Check if answer is correct
                if ($validated['question_format'] === 'multiple_choice') {
                    $correctChoice = collect($questionData['choices'] ?? [])->firstWhere('is_correct', true);
                    if ($correctChoice) {
                        $correctAnswer = $correctChoice['id'];
                        $isCorrect = $userAnswer && (int)$userAnswer === (int)$correctChoice['id'];
                    }
                } else {
                    // Subjective question
                    $userAnswerStr = (string)($userAnswer ?? '');
                    $acceptedVariations = $questionData['accepted_variations'] ?? [];
                    $finalAnswer = $questionData['final_answer'] ?? '';
                    
                    if ($finalAnswer) {
                        $acceptedVariations[] = $finalAnswer;
                    }
                    
                    $normalize = function($s) {
                        return strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^0-9a-z.%°\-\+]/', '', $s))));
                    };
                    
                    $answerType = strtolower($questionData['answer_type'] ?? 'text');
                    $userNormalized = $normalize($userAnswerStr);
                    
                    foreach ($acceptedVariations as $accepted) {
                        if (!$accepted) continue;
                        
                        if ($answerType === 'numeric') {
                            $numA = (float)preg_replace('/[^0-9.\-]/', '', $userAnswerStr);
                            $numB = (float)preg_replace('/[^0-9.\-]/', '', (string)$accepted);
                            if (!is_nan($numA) && !is_nan($numB) && abs($numA - $numB) < 1e-9) {
                                $isCorrect = true;
                                $correctAnswer = $accepted;
                                break;
                            }
                        } else {
                            if ($userNormalized === $normalize((string)$accepted)) {
                                $isCorrect = true;
                                $correctAnswer = $accepted;
                                break;
                            }
                        }
                    }
                }

                if ($isCorrect) {
                    $correctCount++;
                    $marksForQuestion = $totalMarksForQuestion;
                } else {
                    $incorrectCount++;
                }

                $marksObtained += $marksForQuestion;
                $totalMarks += $totalMarksForQuestion;

                // Find question in database to get the actual database ID for foreign key constraint
                // Prioritize finding by question text since original IDs (1,2,3) don't match database IDs
                $dbQuestion = null;
                
                // First, try to find by question text and topic/subtopic (most reliable)
                // Use topic_id and subtopic_id as-is (e.g., "1" and "1.1") - no normalization
                if (isset($questionData['question_text'])) {
                    $dbQuestion = QuestionBank::where('question_text', $questionData['question_text'])
                        ->where('topic_id', $validated['topic_id'])
                        ->where('subtopic_id', $validated['subtopic_id'] ?? null)
                        ->first();
                }
                
                // Fallback: try to find by original ID (only if it's numeric and might exist)
                // This is less reliable since original IDs (1,2,3) usually don't match database IDs
                if (!$dbQuestion && is_numeric($questionId) && $questionId > 0) {
                    $dbQuestion = QuestionBank::find($questionId);
                }
                
                // If still not found, log error and skip this question
                if (!$dbQuestion) {
                    Log::error("Question not found in database", [
                        'original_id' => $questionId,
                        'question_text' => $questionData['question_text'] ?? 'N/A',
                        'topic_id' => $validated['topic_id'],
                        'subtopic_id' => $validated['subtopic_id'] ?? null,
                    ]);
                    continue; // Skip this question
                }
                
                // Use database ID for foreign key constraint
                $finalQuestionId = $dbQuestion->id;

                // Create quiz response
                try {
                    // Get level from question data or use quiz level
                    $questionLevel = $questionData['level'] ?? $quizLevel ?? null;
                    QuizResponse::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $finalQuestionId,
                        'user_id' => $user->id,
                        'topic_id' => $validated['topic_id'],
                        'subtopic_id' => $validated['subtopic_id'] ?? null,
                        'mastery_level' => $questionLevel ? (string)$questionLevel : null, // Store as string for backward compatibility
                        'learning_style' => $validated['learning_style'] ?? null,
                        'question_format' => $validated['question_format'],
                        'student_answer' => $userAnswer ? (string)$userAnswer : null,
                        'correct_answer' => $correctAnswer ? (string)$correctAnswer : null,
                        'is_correct' => $isCorrect,
                        'marks_obtained' => $marksForQuestion,
                        'total_marks' => $totalMarksForQuestion,
                        'answered_at' => $completedAt,
                    ]);
                    $responsesCreated++;
                } catch (\Exception $e) {
                    Log::error("Failed to create quiz response", [
                        'attempt_id' => $attempt->id,
                        'question_id' => $finalQuestionId,
                        'error' => $e->getMessage(),
                    ]);
                    $responsesSkipped++;
                }
            }
            
            // Log summary
            Log::info('Quiz submission summary', [
                'attempt_id' => $attempt->id,
                'total_questions' => count($questions),
                'responses_created' => $responsesCreated,
                'responses_skipped' => $responsesSkipped,
            ]);

            // Exp-based level-up logic: Award 5 exp per correct answer
            $expGained = $correctCount * 5;
            
            // Update attempt with calculated values
            $scorePercentage = $totalMarks > 0 ? ($marksObtained / $totalMarks) * 100 : 0;
            $attempt->update([
                'correct_answers' => $correctCount,
                'incorrect_answers' => $incorrectCount,
                'score_percentage' => round($scorePercentage, 2),
                'total_marks' => $totalMarks,
                'marks_obtained' => $marksObtained,
                'exp_gained' => $expGained,
            ]);
            $currentExp = $user->exp ?? 0;
            $newExp = $currentExp + $expGained;
            
            // Update user's exp
            User::where('id', $user->id)->update(['exp' => $newExp]);
            
            // Check if user can level up based on exp
            $leveledUp = false;
            $newLevel = $currentLevel;
            $levelUpMessage = null;
            
            // Check for level up (can level up multiple times if enough exp)
            // Reload user to ensure we have the latest data
            $userModel = User::find($user->id);
            while ($userModel && $userModel->level < 12) {
                $newLevelAfterCheck = $userModel->checkAndUpdateLevel();
                if ($newLevelAfterCheck) {
                    $leveledUp = true;
                    $newLevel = $newLevelAfterCheck;
                    $levelUpMessage = "Congratulations! You've advanced to Level {$newLevel}! Keep up the excellent work!";
                    Log::info("User {$user->id} leveled up to {$newLevel} with {$newExp} exp");
                    // Reload user to get updated level for next iteration
                    $userModel = User::find($user->id);
                } else {
                    break; // No more level ups possible
                }
            }

            DB::commit();

            // Update analytics after successful quiz submission
            try {
                $analyticsController = new AnalyticsController();
                
                // Update student progress
                $analyticsController->updateStudentProgress(
                    $user->id,
                    $validated['topic_id'],
                    $validated['subtopic_id'] ?? null,
                    (string)$quizLevel, // Pass level as string for backward compatibility
                    [
                        'score_percentage' => $scorePercentage,
                        'total_questions' => count($questions),
                        'correct_answers' => $correctCount,
                        'marks_obtained' => $marksObtained,
                        'total_marks' => $totalMarks,
                        'time_spent_seconds' => $timeSpent,
                    ]
                );
            } catch (\Exception $analyticsError) {
                // Log but don't fail the request if analytics update fails
                Log::warning('Analytics update failed after quiz submission: ' . $analyticsError->getMessage());
            }

            // Calculate exp needed for next level
            $totalExpForCurrentLevel = User::getTotalExpForLevel($newLevel);
            $expInCurrentLevel = max(0, $newExp - $totalExpForCurrentLevel);
            $expNeededForNextLevel = $newLevel < 12 ? User::getExpNeededForLevel($newLevel) : null;
            $totalExpForNextLevel = $newLevel < 12 ? User::getTotalExpForLevel($newLevel + 1) : null;
            // Calculate progress: exp in current level / exp needed for next level
            $expProgress = $expNeededForNextLevel && $expNeededForNextLevel > 0 ? ($expInCurrentLevel / $expNeededForNextLevel) * 100 : 100;

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz attempt submitted successfully',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'correct_count' => $correctCount,
                    'incorrect_count' => $incorrectCount,
                    'total_questions' => count($questions),
                    'score_percentage' => round($scorePercentage, 2),
                    'marks_obtained' => $marksObtained,
                    'total_marks' => $totalMarks,
                    'exp_gained' => $expGained,
                    'exp' => $newExp,
                    'exp_in_current_level' => $expInCurrentLevel,
                    'exp_needed_for_next_level' => $expNeededForNextLevel,
                    'total_exp_for_next_level' => $totalExpForNextLevel,
                    'exp_progress' => round($expProgress, 2),
                    'level' => $newLevel,
                    'leveled_up' => $leveledUp,
                    'level_up_message' => $levelUpMessage,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting quiz attempt: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit quiz attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all quiz attempts for the authenticated user
     */
    public function getAttempts(Request $request)
    {
        $user = Auth::user();
        
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'topic_id' => $attempt->topic_id,
                    'subtopic_id' => $attempt->subtopic_id,
                    'question_format' => $attempt->question_format,
                    'mastery_level' => $attempt->mastery_level,
                    'learning_style' => $attempt->learning_style,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'incorrect_answers' => $attempt->incorrect_answers,
                    'score_percentage' => $attempt->score_percentage,
                    'marks_obtained' => $attempt->marks_obtained,
                    'exp_gained' => $attempt->exp_gained,
                    'total_marks' => $attempt->total_marks,
                    'started_at' => $attempt->started_at?->toISOString(),
                    'completed_at' => $attempt->completed_at?->toISOString(),
                    'time_spent_seconds' => $attempt->time_spent_seconds,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $attempts
        ]);
    }

    /**
     * Get quiz attempt details with responses
     */
    public function getAttemptDetails($id)
    {
        $user = Auth::user();
        
        $attempt = QuizAttempt::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['responses' => function ($query) {
                // Order by created_at to preserve the original question order
                // (since all responses have the same answered_at, ordering by creation time maintains order)
                $query->orderBy('created_at');
            }])
            ->first();

        if (!$attempt) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz attempt not found'
            ], 404);
        }

        // Get question details for each response
        $storedQuestions = $attempt->quiz_parameters['questions'] ?? [];
        $questionMap = [];
        foreach ($storedQuestions as $q) {
            $questionMap[$q['id']] = $q;
        }

        // Also create an indexed array for fallback matching by order
        $storedQuestionsIndexed = array_values($storedQuestions);

        Log::info('MathQuest Attempt Details Retrieved', [
            'attempt_id' => $attempt->id,
            'responses_count' => $attempt->responses->count(),
            'stored_questions_count' => count($storedQuestions),
        ]);

        $responses = $attempt->responses->map(function ($response, $responseIndex) use ($questionMap, $storedQuestionsIndexed, $storedQuestions) {
            // Get question from database (response->question_id is now a database ID)
            $question = QuestionBank::find($response->question_id);
            
            // Find the corresponding stored question by matching question text
            // This is the most reliable way since database IDs don't match original IDs
            $storedQuestion = null;
            if ($question) {
                // Match by question text
                foreach ($storedQuestions as $storedQ) {
                    if (isset($storedQ['question_text']) && 
                        $storedQ['question_text'] === $question->question_text) {
                        $storedQuestion = $storedQ;
                        break;
                    }
                }
            }
            
            // Fallback: if not found by text, try to match by order/index
            // Responses are created in the same order as questions, so this should work
            if (!$storedQuestion && isset($storedQuestionsIndexed[$responseIndex])) {
                $storedQuestion = $storedQuestionsIndexed[$responseIndex];
            }
            
            $questionText = $question?->question_text 
                ?? $storedQuestion['question_text'] 
                ?? 'Question not found';
            
            $questionData = null;
            if ($question) {
                // Use database question (preferred for accuracy)
                // Format choices to match frontend expectations
                $choices = null;
                if ($response->question_format === 'multiple_choice' && $question->choices) {
                    $rawChoices = $question->choices;
                    if (!is_array($rawChoices)) {
                        $rawChoices = json_decode($rawChoices, true) ?? [];
                    }
                    
                    $choices = array_map(function ($choice, $index) {
                        // Handle both formats: ['label' => 'A', 'text' => '...'] or ['answer_text' => '...']
                        return [
                            'id' => $choice['id'] ?? ($index + 1),
                            'label' => $choice['label'] ?? chr(65 + $index), // A, B, C, D
                            'text' => $choice['text'] ?? $choice['answer_text'] ?? '',
                            'answer_text' => $choice['text'] ?? $choice['answer_text'] ?? '', // Ensure answer_text exists
                            'is_correct' => $choice['is_correct'] ?? false,
                        ];
                    }, $rawChoices, array_keys($rawChoices));
                }
                
                $questionData = [
                    'choices' => $choices,
                    'answer_key' => $question->answer_key,
                    'working_steps' => $question->working_steps,
                    'final_answer' => $question->final_answer,
                    'accepted_variations' => $question->accepted_variations,
                    'answer_type' => $question->answer_type,
                    'image_url' => $question->image_url ?? null,
                ];
            } elseif ($storedQuestion) {
                // Use stored question data from quiz_parameters (fallback)
                // Format choices to ensure they have both text and answer_text
                $choices = null;
                if (isset($storedQuestion['choices']) && is_array($storedQuestion['choices'])) {
                    $choices = array_map(function ($choice) {
                        return [
                            'id' => $choice['id'] ?? null,
                            'label' => $choice['label'] ?? null,
                            'text' => $choice['text'] ?? $choice['answer_text'] ?? '',
                            'answer_text' => $choice['text'] ?? $choice['answer_text'] ?? '', // Ensure answer_text exists
                            'is_correct' => $choice['is_correct'] ?? false,
                        ];
                    }, $storedQuestion['choices']);
                }
                
                $questionData = [
                    'choices' => $choices,
                    'answer_key' => $storedQuestion['answer_key'] ?? null,
                    'working_steps' => $storedQuestion['working_steps'] ?? null,
                    'final_answer' => $storedQuestion['final_answer'] ?? null,
                    'accepted_variations' => $storedQuestion['accepted_variations'] ?? null,
                    'answer_type' => $storedQuestion['answer_type'] ?? null,
                    'image_url' => $storedQuestion['image_url'] ?? null,
                ];
            }

            return [
                'id' => $response->id,
                'question_id' => $response->question_id,
                'question_text' => $questionText,
                'question_format' => $response->question_format,
                'mastery_level' => $response->mastery_level,
                'student_answer' => $response->student_answer,
                'correct_answer' => $response->correct_answer,
                'is_correct' => $response->is_correct,
                'marks_obtained' => $response->marks_obtained,
                'total_marks' => $response->total_marks,
                'answered_at' => $response->answered_at?->toISOString(),
                'question_data' => $questionData,
            ];
        });

        // Ensure responses is always an array
        $responsesArray = $responses->toArray();
        
        Log::info('getAttemptDetails - Returning response', [
            'attempt_id' => $attempt->id,
            'responses_count' => count($responsesArray),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'attempt' => [
                    'id' => $attempt->id,
                    'topic_id' => $attempt->topic_id,
                    'subtopic_id' => $attempt->subtopic_id,
                    'question_format' => $attempt->question_format,
                    'mastery_level' => $attempt->mastery_level,
                    'learning_style' => $attempt->learning_style,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'incorrect_answers' => $attempt->incorrect_answers,
                    'score_percentage' => $attempt->score_percentage,
                    'marks_obtained' => $attempt->marks_obtained,
                    'total_marks' => $attempt->total_marks,
                    'started_at' => $attempt->started_at?->toISOString(),
                    'completed_at' => $attempt->completed_at?->toISOString(),
                    'time_spent_seconds' => $attempt->time_spent_seconds,
                ],
                'responses' => $responsesArray,
            ]
        ]);
    }
}
