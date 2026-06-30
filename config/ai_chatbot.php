<?php

/**
 * AI chatbot API settings.
 *
 * Map your curl request to these env vars:
 * - URL  → AI_CHATBOT_API_URL
 * - Authorization header → AI_CHATBOT_AUTH_TYPE + AI_CHATBOT_API_KEY
 * - model / body fields → AI_CHATBOT_MODEL, AI_CHATBOT_MESSAGES_KEY
 * - response JSON path → AI_CHATBOT_RESPONSE_PATH (dot notation)
 */
return [
    'enabled' => (bool) env('AI_CHATBOT_ENABLED', false),

    'api_url' => env('AI_CHATBOT_API_URL', env('OPENAI_BASE_URL', 'https://api.openai.com/v1/chat/completions')),

    'api_key' => env('OPENAI_API_KEY', env('AI_CHATBOT_API_KEY')),

    /**
     * bearer: Authorization: Bearer {key}
     * api-key: custom header (see api_key_header)
     * none: no auth header (key may be in URL or extra payload)
     */
    'auth_type' => env('AI_CHATBOT_AUTH_TYPE', 'bearer'),

    'api_key_header' => env('AI_CHATBOT_API_KEY_HEADER', 'x-api-key'),

    'model' => env('AI_CHATBOT_MODEL', 'gpt-4o-mini'),

    'messages_key' => env('AI_CHATBOT_MESSAGES_KEY', 'messages'),

    'response_path' => env('AI_CHATBOT_RESPONSE_PATH', 'choices.0.message.content'),

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
