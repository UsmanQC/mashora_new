<?php

/**
 * AI chatbot API settings.
 *
 * OpenAI Responses API (default — matches curl /v1/responses):
 * - AI_CHATBOT_API_DRIVER=responses
 * - AI_CHATBOT_API_URL=https://api.openai.com/v1/responses
 * - AI_CHATBOT_MODEL=gpt-5.4-mini
 * - AI_CHATBOT_STORE=true
 *
 * Legacy Chat Completions:
 * - AI_CHATBOT_API_DRIVER=chat_completions
 * - AI_CHATBOT_API_URL=https://api.openai.com/v1/chat/completions
 * - AI_CHATBOT_RESPONSE_PATH=choices.0.message.content
 */
return [
    'enabled' => (bool) env('AI_CHATBOT_ENABLED', false),

    /**
     * responses: POST /v1/responses with input + instructions
     * chat_completions: POST /v1/chat/completions with messages
     */
    'api_driver' => env('AI_CHATBOT_API_DRIVER', 'responses'),

    'api_url' => env('AI_CHATBOT_API_URL', 'https://api.openai.com/v1/responses'),

    'api_key' => env('OPENAI_API_KEY', env('AI_CHATBOT_API_KEY')),

    /**
     * bearer: Authorization: Bearer {key}
     * api-key: custom header (see api_key_header)
     * none: no auth header (key may be in URL or extra payload)
     */
    'auth_type' => env('AI_CHATBOT_AUTH_TYPE', 'bearer'),

    'api_key_header' => env('AI_CHATBOT_API_KEY_HEADER', 'x-api-key'),

    'model' => env('AI_CHATBOT_MODEL', 'gpt-5.4-mini'),

    /** Persist responses on OpenAI side (Responses API `store` field). */
    'store' => (bool) env('AI_CHATBOT_STORE', true),

    'messages_key' => env('AI_CHATBOT_MESSAGES_KEY', 'messages'),

    'response_path' => env('AI_CHATBOT_RESPONSE_PATH', 'output.0.content.0.text'),

    'timeout' => (int) env('AI_CHATBOT_TIMEOUT', 60),

    'max_messages' => (int) env('AI_CHATBOT_MAX_MESSAGES', 20),

    'max_message_length' => (int) env('AI_CHATBOT_MAX_MESSAGE_LENGTH', 2000),

    'system_prompt' => env('AI_CHATBOT_SYSTEM_PROMPT', <<<'PROMPT'
أنت المساعد الذكي لمنصة أوان للرعاية النفسية. أجب بالعربية بأسلوب دافئ ومختصر.
ساعد الزائر على فهم خدمات المنصة (استشارات أونلاين، جلسات مع مختصين مرخصين، خصوصية وأمان).
لا تقدّم تشخيصاً طبياً ولا وصف أدوية. إذا احتاج المستخدم دعماً عاجلاً، شجّعه على التواصل مع مختص أو خط مساعدة محلي.
PROMPT),

    /**
     * Optional JSON object merged into the API request body (e.g. temperature, max_tokens).
     *
     * @var array<string, mixed>|null
     */
    'extra_payload' => json_decode((string) env('AI_CHATBOT_EXTRA_PAYLOAD', ''), true) ?: [],

    'cost_per_request_cents' => (int) env('AI_CHATBOT_COST_PER_REQUEST_CENTS', 0),
];
