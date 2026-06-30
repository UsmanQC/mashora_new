<?php

namespace App\Services\AiChatbot;

use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AiConversationRecorder
{
    private const SESSION_CONVERSATION_KEY = 'ai_chatbot.conversation_id';

    public function resolveConversation(Request $request): AiConversation
    {
        $conversationId = session(self::SESSION_CONVERSATION_KEY);

        if (is_string($conversationId) && $conversationId !== '') {
            $existing = AiConversation::query()->find($conversationId);

            if ($existing instanceof AiConversation) {
                return $existing;
            }
        }

        $user = $request->user();

        $conversation = AiConversation::query()->create([
            'user_id' => $user instanceof User ? $user->id : null,
            'session_id' => (string) session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        session([self::SESSION_CONVERSATION_KEY => $conversation->id]);

        return $conversation;
    }

    public function recordUserMessage(AiConversation $conversation, string $content): void
    {
        $this->recordMessage($conversation, 'user', $content);
    }

    public function recordAssistantMessage(AiConversation $conversation, string $content): void
    {
        $this->recordMessage($conversation, 'assistant', $content);
    }

    /**
     * @param  array<string, mixed>|null  $toolCalls
     */
    public function recordToolMessage(AiConversation $conversation, string $toolName, ?string $content = null, ?array $toolCalls = null): void
    {
        AiConversationMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'tool',
            'content' => $content,
            'tool_name' => $toolName,
            'tool_calls' => $toolCalls,
        ]);
    }

    public function addUsage(AiConversation $conversation, int $promptTokens, int $completionTokens): void
    {
        $conversation->increment('prompt_tokens', $promptTokens);
        $conversation->increment('completion_tokens', $completionTokens);
        $conversation->increment('total_tokens', $promptTokens + $completionTokens);

        $cost = (int) config('ai_chatbot.cost_per_request_cents', 0);

        if ($cost > 0) {
            $conversation->increment('estimated_cost_cents', $cost);
            AiSetting::current()->increment('estimated_cost_cents', $cost);
        }
    }

    public function resetSession(): void
    {
        session()->forget(self::SESSION_CONVERSATION_KEY);
        session()->forget('ai_chatbot.messages');
    }

    private function recordMessage(AiConversation $conversation, string $role, string $content): void
    {
        AiConversationMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
        ]);
    }
}
