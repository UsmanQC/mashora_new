<?php

use App\Models\AiSetting;
use App\Models\Faq;
use App\Services\AiChatbot\AiChatbotToolManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ai_chatbot.enabled' => true,
        'ai_chatbot.api_driver' => 'responses',
        'ai_chatbot.api_url' => 'https://api.example.com/v1/responses',
        'ai_chatbot.api_key' => 'test-key',
        'ai_chatbot.model' => 'gpt-5.4-mini',
        'ai_chatbot.store' => true,
    ]);
});

test('api chat endpoint returns assistant reply via responses api', function () {
    Http::fake([
        'https://api.example.com/v1/responses' => Http::response([
            'output' => [
                [
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        ['type' => 'output_text', 'text' => 'مرحباً بك في أوان'],
                    ],
                ],
            ],
        ]),
    ]);

    $this->postJson(route('api.chat'), [
        'message' => 'مرحبا',
    ])
        ->assertSuccessful()
        ->assertJson([
            'reply' => 'مرحباً بك في أوان',
        ]);
});

test('responses api handles function call loop', function () {
    Faq::factory()->create([
        'question' => 'How do I book?',
        'answer' => 'Use the patient portal.',
        'is_active' => true,
    ]);

    Http::fake([
        'https://api.example.com/v1/responses' => Http::sequence()
            ->push([
                'output' => [
                    [
                        'type' => 'function_call',
                        'call_id' => 'call_123',
                        'name' => 'searchFAQ',
                        'arguments' => json_encode(['query' => 'book']),
                    ],
                ],
            ])
            ->push([
                'output' => [
                    [
                        'type' => 'message',
                        'role' => 'assistant',
                        'content' => [
                            ['type' => 'output_text', 'text' => 'You can book via the patient portal.'],
                        ],
                    ],
                ],
            ]),
    ]);

    $this->postJson(route('api.chat'), [
        'message' => 'How do I book?',
    ])
        ->assertSuccessful()
        ->assertJson([
            'reply' => 'You can book via the patient portal.',
        ]);
});

test('chat completions driver still works when configured', function () {
    config([
        'ai_chatbot.api_driver' => 'chat_completions',
        'ai_chatbot.api_url' => 'https://api.example.com/v1/chat/completions',
        'ai_chatbot.model' => 'gpt-4o-mini',
        'ai_chatbot.response_path' => 'choices.0.message.content',
    ]);

    Http::fake([
        'https://api.example.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Hello from chat completions',
                    ],
                ],
            ],
        ]),
    ]);

    $this->postJson(route('api.chat'), [
        'message' => 'hello',
    ])
        ->assertSuccessful()
        ->assertJson([
            'reply' => 'Hello from chat completions',
        ]);
});

test('chatbot validates message input', function () {
    $this->postJson(route('api.chat'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);
});

test('chatbot returns service unavailable when not configured', function () {
    config(['ai_chatbot.enabled' => false]);

    $this->postJson(route('api.chat'), [
        'message' => 'hello',
    ])->assertStatus(503);
});

test('chat history can be reset via api', function () {
    session(['ai_chatbot.messages' => [
        ['role' => 'user', 'content' => 'test'],
    ]]);

    $this->deleteJson(route('api.chat.reset'))
        ->assertSuccessful()
        ->assertJson(['ok' => true]);

    expect(session('ai_chatbot.messages'))->toBeNull();
});

test('homepage always includes chatbot widget and nav option', function () {
    config(['ai_chatbot.enabled' => false]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('id="awaan-ai-chatbot"', false)
        ->assertSee('المساعد الذكي', false)
        ->assertSee('data-open-ai-chatbot', false)
        ->assertSee('id="awaan-ai-chatbot-locale-switch"', false)
        ->assertSee('data-chatbot-locale="en"', false)
        ->assertSee('renderQuickActions', false)
        ->assertSee('showWelcomeState', false)
        ->assertSee('quickActions', false);
});

test('chatbot api accepts locale and returns localized errors', function () {
    config(['ai_chatbot.enabled' => false]);

    $this->postJson(route('api.chat'), [
        'message' => 'hello',
        'locale' => 'en',
    ])
        ->assertStatus(503)
        ->assertJson([
            'message' => __('ai_chatbot.not_configured', [], 'en'),
        ]);
});

test('patient portal never includes chatbot widget', function () {
    config(['ai_chatbot.enabled' => true]);

    $this->get(route('patient.phone'))
        ->assertSuccessful()
        ->assertDontSee('id="awaan-ai-chatbot"', false);
});

test('search faq tool returns active faqs', function () {
    Faq::factory()->create([
        'question' => 'How do I book?',
        'answer' => 'Use the patient portal.',
        'is_active' => true,
    ]);

    $result = app(AiChatbotToolManager::class)->execute('searchFAQ', [
        'query' => 'book',
    ]);

    expect($result)->toContain('How do I book?');
});

test('search therapists tool returns structured json', function () {
    $result = app(AiChatbotToolManager::class)->execute('searchTherapists', [
        'query' => 'anxiety',
    ]);

    $decoded = json_decode($result, true);

    expect($decoded)->toHaveKey('therapists')
        ->and($decoded)->toHaveKey('count');
});

test('tool manager exposes responses api tool definitions', function () {
    $definitions = app(AiChatbotToolManager::class)->responsesDefinitions();

    expect($definitions)->not->toBeEmpty()
        ->and($definitions[0])->toHaveKeys(['type', 'name', 'description', 'parameters'])
        ->and($definitions[0]['type'])->toBe('function')
        ->and($definitions[0])->not->toHaveKey('function');
});

test('book appointment alias executes booking flow', function () {
    $manager = app(AiChatbotToolManager::class);

    $result = json_decode($manager->execute('book_appointment', [
        'consultation_type' => 'psychological',
        'preferred_date' => 'tomorrow',
        'preferred_time' => 'afternoon',
        'query' => 'anxiety',
    ]), true);

    expect($result)->toHaveKeys(['filter_url', 'message', 'consultation_type'])
        ->and($result['consultation_type'])->toBe('psychological');
});

test('effective system prompt includes b2c conversation flow rules', function () {
    $prompt = AiSetting::current()->effectiveSystemPrompt();

    expect($prompt)
        ->toContain('bookAppointment')
        ->toContain('searchTherapists');
});
