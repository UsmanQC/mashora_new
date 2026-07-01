<?php

namespace App\Services\AiChatbot;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class OpenAiResponsesApi
{
    private const MAX_TOOL_ITERATIONS = 5;

    public function __construct(
        private readonly AiChatbotToolManager $tools,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $conversation
     */
    public function reply(array $conversation, string $instructions, PendingRequest $client): string
    {
        /** @var list<array<string, mixed>> $input */
        $input = $this->conversationToInput($conversation);

        $payload = array_merge(
            (array) config('ai_chatbot.extra_payload', []),
            [
                'model' => (string) config('ai_chatbot.model'),
                'instructions' => $instructions,
                'input' => $input,
                'tools' => $this->tools->responsesDefinitions(),
                'tool_choice' => 'auto',
                'store' => (bool) config('ai_chatbot.store', true),
            ],
        );

        for ($iteration = 0; $iteration < self::MAX_TOOL_ITERATIONS; $iteration++) {
            $response = $client->post((string) config('ai_chatbot.api_url'), $payload);

            if (! $response->successful()) {
                Log::warning('AI chatbot Responses API error', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                throw new RuntimeException('AI chatbot request failed.');
            }

            /** @var array<string, mixed> $body */
            $body = $response->json() ?? [];

            /** @var list<array<string, mixed>> $output */
            $output = is_array($body['output'] ?? null) ? $body['output'] : [];

            $functionCalls = array_values(array_filter(
                $output,
                static fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'function_call',
            ));

            if ($functionCalls !== []) {
                $input = array_merge($input, $output);

                foreach ($functionCalls as $functionCall) {
                    $arguments = json_decode((string) ($functionCall['arguments'] ?? '{}'), true);
                    $name = (string) ($functionCall['name'] ?? '');

                    if (! is_array($arguments)) {
                        $arguments = [];
                    }

                    $input[] = [
                        'type' => 'function_call_output',
                        'call_id' => (string) ($functionCall['call_id'] ?? ''),
                        'output' => $this->tools->execute($name, $arguments),
                    ];
                }

                $payload['input'] = $input;

                continue;
            }

            $content = $this->extractAssistantText($body);

            if ($content === null || trim($content) === '') {
                Log::warning('AI chatbot Responses API empty response', ['body' => $body]);

                throw new RuntimeException('AI chatbot returned an empty response.');
            }

            return trim($content);
        }

        throw new RuntimeException('AI chatbot exceeded tool iteration limit.');
    }

    /**
     * @param  list<array{role: string, content: string}>  $conversation
     * @return list<array<string, mixed>>
     */
    private function conversationToInput(array $conversation): array
    {
        $input = [];

        foreach ($conversation as $message) {
            $role = (string) ($message['role'] ?? '');
            $content = trim((string) ($message['content'] ?? ''));

            if ($content === '' || ! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $input[] = [
                'type' => 'message',
                'role' => $role,
                'content' => $content,
            ];
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractAssistantText(array $response): ?string
    {
        /** @var list<array<string, mixed>> $output */
        $output = is_array($response['output'] ?? null) ? $response['output'] : [];

        $text = $this->textFromOutput($output);

        if ($text !== null) {
            return $text;
        }

        $configuredPath = (string) config('ai_chatbot.response_path', '');

        if ($configuredPath !== '' && $configuredPath !== 'output_text') {
            $fallback = data_get($response, $configuredPath);

            if (is_string($fallback) && trim($fallback) !== '') {
                return trim($fallback);
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $output
     */
    private function textFromOutput(array $output): ?string
    {
        $parts = [];

        foreach ($output as $item) {
            if (($item['type'] ?? null) !== 'message' || ($item['role'] ?? null) !== 'assistant') {
                continue;
            }

            $content = $item['content'] ?? [];

            if (! is_array($content)) {
                continue;
            }

            foreach ($content as $part) {
                if (! is_array($part)) {
                    continue;
                }

                if (($part['type'] ?? null) === 'output_text' && is_string($part['text'] ?? null)) {
                    $parts[] = $part['text'];
                }
            }
        }

        $text = trim(implode("\n", $parts));

        return $text !== '' ? $text : null;
    }
}
