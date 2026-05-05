<?php

/**
 * MyFatoorah settings for the patient checkout + MyFatoorah package.
 *
 * Supports env keys used in Mashorapwa / mashora_update:
 * - MYFATOORAH_API_KEY or legacy MYFATOORAH_TOKEN
 * - MYFATOORAH_TEST_MODE, or MYFATOORAH_MODE=live (live) / anything else (test)
 * - MYFATOORAH_COUNTRY (maps to vc_code)
 */
return [
    /**
     * API Token Key (string)
     * Live token: https://docs.myfatoorah.com/docs/live-token
     * Test token: https://docs.myfatoorah.com/docs/test-token
     */
    'api_key' => env('MYFATOORAH_API_KEY') !== null && env('MYFATOORAH_API_KEY') !== ''
        ? (string) env('MYFATOORAH_API_KEY')
        : (string) env('MYFATOORAH_TOKEN', ''),
    /**
     * Test Mode (boolean)
     * When MYFATOORAH_TEST_MODE is set it wins; otherwise MYFATOORAH_MODE=live means production.
     */
    'is_test' => (function (): bool {
        $explicit = env('MYFATOORAH_TEST_MODE');

        if ($explicit !== null && $explicit !== '') {
            return filter_var($explicit, FILTER_VALIDATE_BOOLEAN);
        }

        return env('MYFATOORAH_MODE', 'test') !== 'live';
    })(),
    /**
     * Vendor Country ISO Code (string)
     * Accepted value: KWT, SAU, ARE, QAT, BHR, OMN, JOD, or EGY
     */
    'vc_code' => env('MYFATOORAH_COUNTRY', 'SAU'),
    /**
     * Save card (boolean)
     */
    'save_card' => filter_var(env('MYFATOORAH_SAVE_CARD', true), FILTER_VALIDATE_BOOLEAN),
    /**
     * Webhook secret key (string)
     * The webhook endpoint is: https://{example.com}/myfatoorah/webhook
     */
    'webhook_secret_key' => env('MYFATOORAH_WEBHOOK_SECRET', ''),
    /**
     * Register Apple Pay (boolean)
     */
    'register_apple_pay' => filter_var(env('MYFATOORAH_REGISTER_APPLE_PAY', false), FILTER_VALIDATE_BOOLEAN),
    /**
     * Supplier code (integer)
     */
    'supplier_code' => null,
];
