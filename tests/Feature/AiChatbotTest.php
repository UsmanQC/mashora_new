<?php

use App\Models\Faq;
use App\Services\AiChatbot\AiChatbotToolManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ai_chatbot.enabled' => true,
        'ai_chatbot.api_url' => 'https://api.example.com/v1/chat/completions',
        'ai_chatbot.api_key' => 'test-key',
        'ai_chatbot.model' => 'gpt-4o-mini',
    ]);
});

test('api chat endpoint returns assistant reply', function () {
    Http::fake([
        'https://api.example.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'مرحباً بك في أوان',
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
        ->assertSee('data-open-ai-chatbot', false);
});

test('patient portal hides chatbot widget when disabled', function () {
    config(['ai_chatbot.enabled' => false]);

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
