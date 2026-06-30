<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Services\AiChatbot\AiChatbotToolManager;
use App\Services\AiChatbot\OpenAiResponsesApi;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final class AiChatbotService
{
    private const MAX_TOOL_ITERATIONS = 5;

    public function __construct(
        private readonly AiChatbotToolManager $tools,
        private readonly OpenAiResponsesApi $responsesApi,
    ) {}

    /**
     * @param  list<array{role: string, content?: string|null, tool_calls?: mixed, tool_call_id?: string, name?: string}>  $conversation
     */
    public function reply(array $conversation, string $locale = 'ar'): string
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('AI chatbot is not configured.');
        }

        $settings = AiSetting::current();

        if (! $settings->is_active) {
            throw new RuntimeException('AI chatbot is disabled.');
        }

        $languageRule = $locale === 'ar'
            ? 'Always respond in Arabic unless the user explicitly asks for another language.'
            : 'Always respond in English unless the user explicitly asks for another language.';

        $instructions = $settings->effectiveSystemPrompt()."\n\n".$languageRule;

        return match ((string) config('ai_chatbot.api_driver', 'responses')) {
            'chat_completions' => $this->replyViaChatCompletions($conversation, $instructions),
            default => $this->responsesApi->reply($conversation, $instructions, $this->client()),
        };
    }

    /**
     * @param  list<array{role: string, content?: string|null, tool_calls?: mixed, tool_call_id?: string, name?: string}>  $conversation
     */
    private function replyViaChatCompletions(array $conversation, string $instructions): string
    {
        $messages = array_merge(
            [
                [
                    'role' => 'system',
                    'content' => $instructions,
                ],
            ],
            $conversation,
        );

        $payload = array_merge(
            (array) config('ai_chatbot.extra_payload', []),
            [
                'model' => (string) config('ai_chatbot.model'),
                'messages' => $messages,
                'tools' => $this->tools->definitions(),
                'tool_choice' => 'auto',
            ],
        );

        for ($iteration = 0; $iteration < self::MAX_TOOL_ITERATIONS; $iteration++) {
            $response = $this->client()->post((string) config('ai_chatbot.api_url'), $payload);

            if (! $response->successful()) {
                Log::warning('AI chatbot Chat Completions API error', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                throw new RuntimeException('AI chatbot request failed.');
            }

            $message = data_get($response->json(), 'choices.0.message');

            if (! is_array($message)) {
                throw new RuntimeException('AI chatbot returned an invalid response.');
            }

            $toolCalls = $message['tool_calls'] ?? null;

            if (is_array($toolCalls) && $toolCalls !== []) {
                $payload['messages'][] = [
                    'role' => 'assistant',
                    'content' => $message['content'] ?? null,
                    'tool_calls' => $toolCalls,
                ];

                foreach ($toolCalls as $toolCall) {
                    if (! is_array($toolCall)) {
                        continue;
                    }

                    $function = is_array($toolCall['function'] ?? null) ? $toolCall['function'] : [];
                    $name = (string) ($function['name'] ?? '');
                    $argumentsJson = (string) ($function['arguments'] ?? '{}');
                    $arguments = json_decode($argumentsJson, true);

                    if (! is_array($arguments)) {
                        $arguments = [];
                    }

                    $toolResult = $this->tools->execute($name, $arguments);

                    $payload['messages'][] = [
                        'role' => 'tool',
                        'tool_call_id' => (string) ($toolCall['id'] ?? Str::uuid()),
                        'name' => $name,
                        'content' => $toolResult,
                    ];
                }

                continue;
            }

            $content = $message['content'] ?? data_get($response->json(), (string) config('ai_chatbot.response_path'));

            if (! is_string($content) || trim($content) === '') {
                Log::warning('AI chatbot empty response', ['body' => $response->json()]);

                throw new RuntimeException('AI chatbot returned an empty response.');
            }

            return trim($content);
        }

        throw new RuntimeException('AI chatbot exceeded tool iteration limit.');
    }

    public function isConfigured(): bool
    {
        if (! (bool) config('ai_chatbot.enabled')) {
            return false;
        }

        if ((string) config('ai_chatbot.api_url') === '') {
            return false;
        }

        $authType = (string) config('ai_chatbot.auth_type', 'bearer');

        if ($authType !== 'none' && (string) config('ai_chatbot.api_key') === '') {
            return false;
        }

        return true;
    }

    private function client(): PendingRequest
    {
        $client = Http::timeout((int) config('ai_chatbot.timeout', 60))
            ->acceptJson()
            ->asJson();

        $apiKey = (string) config('ai_chatbot.api_key');

        return match ((string) config('ai_chatbot.auth_type', 'bearer')) {
            'api-key' => $client->withHeaders([
                (string) config('ai_chatbot.api_key_header', 'x-api-key') => $apiKey,
            ]),
            'none' => $client,
            default => $client->withToken($apiKey),
        };
    }
}
