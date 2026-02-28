<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\PracticeChatMessage;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends BaseController
{
    private function getRagApiUrl(): string
    {
        return config('services.chatbot.url');
    }

    private function getRagApiTimeout(): int
    {
        return config('services.chatbot.timeout');
    }

    /**
     * Format answer based on question type
     */
    private function formatAnswer($question, $answerKey)
    {
        if ($question->question_format === 'multiple_choice') {
            $choices = $question->choices ?? [];
            if (is_string($choices)) {
                $choices = json_decode($choices, true) ?? [];
            }

            // Find the choice with matching label
            foreach ($choices as $choice) {
                if (($choice['label'] ?? '') === $answerKey) {
                    return $choice['text'] ?? $choice['answer_text'] ?? $answerKey;
                }
            }
            return $answerKey;
        } elseif ($question->question_format === 'subjective') {
            return $question->final_answer ?? $answerKey;
        }

        return $answerKey;
    }

    /**
     * Send message to chatbot API
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|string',
            'user_prompt' => 'required|string|max:1000',
            'practice_session_id' => 'nullable|string',
        ]);

        $user = $request->user();

        // Generate practice_session_id if not provided
        $practiceSessionId = $validated['practice_session_id'] ?? (string) Str::uuid();
        $questionId = $validated['question_id'];
        $userPrompt = $validated['user_prompt'];

        try {
            // Get question from database
            $question = QuestionBank::find($questionId);
            if (!$question) {
                return $this->sendError('Question not found', [], 404);
            }

            // Get user's VARK style
            $varkStyle = optional($user->varkResult)->dominant_style ?? 'Visual';

            // Format answer
            $answer = $this->formatAnswer($question, $question->answer_key);

            // Get chat history for this question and session
            $chatHistory = PracticeChatMessage::where('practice_session_id', $practiceSessionId)
                ->where('question_id', $questionId)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'role' => $message->role,
                        'content' => $message->content,
                    ];
                })
                ->toArray();

            // Build payload for external API
            $payload = [
                'practice_session_id' => $practiceSessionId,
                'vark_style' => $varkStyle,
                'question' => $question->question_text,
                'answer' => $answer,
                'topic' => (string) $question->topic_id,
                'subtopic' => $question->subtopic_id ? (string) $question->subtopic_id : null,
                'user_prompt' => $userPrompt,
                'chat_history' => $chatHistory,
            ];

            // Save user message to database
            PracticeChatMessage::create([
                'practice_session_id' => $practiceSessionId,
                'question_id' => $questionId,
                'user_id' => $user->id,
                'role' => 'user',
                'content' => $userPrompt,
            ]);

            // Call external chatbot API
            $response = Http::timeout($this->getRagApiTimeout())
                ->post($this->getRagApiUrl(), $payload);

            if ($response->failed()) {
                Log::error('Chatbot API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->sendError('Failed to get response from chatbot', [], 500);
            }

            $responseData = $response->json();

            Log::info('Chatbot API Response', ['response' => $responseData]);

            // Extract response from the API structure
            $assistantMessage = $responseData['data']['response'] ??
                $responseData['response'] ??
                $responseData['content'] ??
                $responseData['message'] ??
                'I apologize, but I could not generate a response.';

            // Save assistant response to database
            PracticeChatMessage::create([
                'practice_session_id' => $practiceSessionId,
                'question_id' => $questionId,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => $assistantMessage,
            ]);

            return $this->sendResponse([
                'message' => $assistantMessage,
                'practice_session_id' => $practiceSessionId,
                'language' => $responseData['data']['language'] ?? null,
                'intent' => $responseData['data']['intent'] ?? null,
                'metadata' => $responseData['data']['metadata'] ?? null,
            ], 'Message sent successfully');
        } catch (\Exception $e) {
            Log::error('Error in ChatbotController::sendMessage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->sendError('An error occurred while processing your message', [], 500);
        }
    }

    /**
     * Get chat history for a question
     */
    public function getChatHistory(Request $request)
    {
        $validated = $request->validate([
            'practice_session_id' => 'required|string',
            'question_id' => 'required|string',
        ]);

        $user = $request->user();

        $messages = PracticeChatMessage::where('practice_session_id', $validated['practice_session_id'])
            ->where('question_id', $validated['question_id'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at->toISOString(),
                ];
            });

        return $this->sendResponse([
            'messages' => $messages,
        ], 'Chat history retrieved successfully');
    }
}
