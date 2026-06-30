<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatbotMessageRequest;
use App\Services\AiChatbot\AiConversationRecorder;
use App\Services\AiChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiChatbotController extends Controller
{
    private const SESSION_MESSAGES_KEY = 'ai_chatbot.messages';

    public function store(AiChatbotMessageRequest $request, AiChatbotService $chatbot, AiConversationRecorder $recorder): JsonResponse
    {
        if (! $chatbot->isConfigured()) {
            return response()->json([
                'message' => __('ai_chatbot.not_configured'),
            ], 503);
        }

        $conversation = $recorder->resolveConversation($request);

        /** @var list<array{role: string, content: string}> $history */
        $history = session(self::SESSION_MESSAGES_KEY, []);

        $userMessage = $request->validated('message');

        $history[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        $maxMessages = (int) config('ai_chatbot.max_messages', 20);
        if (count($history) > $maxMessages) {
            $history = array_slice($history, -$maxMessages);
        }

        $recorder->recordUserMessage($conversation, $userMessage);

        try {
            $reply = $chatbot->reply($history);
        } catch (Throwable $exception) {
            Log::error('AI chatbot reply failed', [
                'exception' => $exception->getMessage(),
            ]);

            array_pop($history);

            return response()->json([
                'message' => __('ai_chatbot.request_failed'),
            ], 502);
        }

        $history[] = [
            'role' => 'assistant',
            'content' => $reply,
        ];

        session([self::SESSION_MESSAGES_KEY => $history]);
        $recorder->recordAssistantMessage($conversation, $reply);

        return response()->json([
            'reply' => $reply,
        ]);
    }

    public function destroy(AiConversationRecorder $recorder): JsonResponse
    {
        $recorder->resetSession();

        return response()->json([
            'ok' => true,
        ]);
    }
}
