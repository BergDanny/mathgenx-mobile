<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MathPracticeController extends BaseController
{
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
     * Format question based on type
     */
    private function formatQuestion($questionData, $questionFormat)
    {
        switch ($questionFormat) {
            case 'multiple_choice':
                return $this->formatMultipleChoiceQuestion($questionData);
            default:
                return $this->formatMultipleChoiceQuestion($questionData);
        }
    }

    /**
     * Format database questions to match API response format
     */
    private function formatDatabaseQuestions($questions, $questionFormat)
    {
        return $questions->map(function ($question) use ($questionFormat) {
            $questionData = [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'learning_style' => $question->learning_style,
                'question_format' => $question->question_format,
                'topic_id' => $question->topic_id,
                'subtopic_id' => $question->subtopic_id,
                'mastery_level' => $question->mastery_level,
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

    /**
     * Get practice questions from question bank
     */
    public function getQuestions(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'topic' => 'required|string|between:1,10',
            'subtopic' => 'nullable|string',
            'question_format' => 'required|string|in:multiple_choice,subjective',
            'language' => 'nullable|string|in:english,malay',
            'tp' => 'required|integer|min:1|max:6',
            'difficulty' => 'required|string|in:easy,hard',
        ]);

        $validated['language'] = $validated['language'] ?? 'english';

        // Get authenticated user
        $user = $request->user();

        // Get VARK style from user's profile
        $userVark = optional($user->varkResult ?? null)->dominant_style ?? null;
        $learningStyle = $userVark ?? 'Visual'; // Fallback to 'Visual' if not set

        // Extract parameters
        $topicId = $validated['topic'];
        $subtopicId = $validated['subtopic'] ?? null;
        $questionFormat = $validated['question_format'];
        $tp = $validated['tp'];
        $difficulty = $validated['difficulty'];
        $language = $validated['language'];

        // Map TP + difficulty to mastery_level format (e.g., "TP1_easy", "TP2_hard")
        $masteryLevel = "TP{$tp}_{$difficulty}";

        Log::info('MathPractice getQuestions called with parameters:', [
            'topic' => $topicId,
            'subtopic' => $subtopicId,
            'question_format' => $questionFormat,
            'tp' => $tp,
            'difficulty' => $difficulty,
            'mastery_level' => $masteryLevel,
            'learning_style' => $learningStyle,
            'language' => $language,
            'user_id' => $user->id,
        ]);

        try {
            // Query question_bank table directly
            $query = QuestionBank::where('topic_id', $topicId)
                ->where('question_format', $questionFormat)
                ->where('mastery_level', $masteryLevel)
                ->where('learning_style', $learningStyle)
                ->where('language', $language);

            if ($subtopicId) {
                $query->where('subtopic_id', $subtopicId);
            }

            $questions = $query->inRandomOrder()->limit(10)->get();

            if ($questions->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no question available yet'
                ], 404);
            }

            // Format questions
            $formattedQuestions = $this->formatDatabaseQuestions($questions, $questionFormat);
            $formattedQuestions = array_map(function ($questionData) use ($questionFormat) {
                return $this->formatQuestion($questionData, $questionFormat);
            }, $formattedQuestions);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'questions' => $formattedQuestions,
                    'total_requested' => 10,
                    'total_returned' => count($formattedQuestions),
                    'question_format' => $questionFormat,
                    'source' => 'question_bank',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in MathPractice getQuestions: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve practice questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

