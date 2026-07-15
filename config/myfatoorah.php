<?php

$mode = strtolower((string) env('MYFATOORAH_MODE', 'test'));
$isLiveMode = in_array($mode, ['live', 'production'], true);
// Live mode must hit MyFatoorah live API. Do not set MYFATOORAH_TEST_MODE=true on production.
$isTest = ! $isLiveMode;

$defaultTestKey = 'SK_KWT_vVZlnnAqu8jRByOWaRPNId4ShzEDNt256dvnjebuyzo52dXjAfRx2ixW5umjWSUx';
$testApiKey = (string) env('MYFATOORAH_TEST_API_KEY', $defaultTestKey);
$liveApiKey = (string) env('MYFATOORAH_API_KEY', env('MYFATOORAH_TOKEN', ''));

return [
    /**
     * API Token Key (string)
     * Accepted value:
     * Live Token: https://myfatoorah.readme.io/docs/live-token
     * Test Token: https://myfatoorah.readme.io/docs/test-token
     */
    'api_key' => $isTest ? $testApiKey : $liveApiKey,
    'test_api_key' => $testApiKey,
    /**
     * Test Mode (boolean)
     * Accepted value: true for the test mode or false for the live mode
     */
    'test_mode' => $isTest,
    'is_test' => $isTest,
    /**
     * Country ISO Code (string)
     * Accepted value: KWT, SAU, ARE, QAT, BHR, OMN, JOD, or EGY.
     */
    'country_iso' => (string) env('MYFATOORAH_COUNTRY', 'SAU'),
    'vc_code' => (string) env('MYFATOORAH_COUNTRY', 'SAU'),
    /**
     * Save card (boolean)
     * Accepted value: true if you want to enable save card options.
     * You should contact your account manager to enable this feature in your MyFatoorah account as well.
     */
    'save_card' => (bool) env('MYFATOORAH_SAVE_CARD', true),
    /**
     * Webhook secret key (string)
     * Enable webhook on your MyFatoorah account setting then paste the secret key here.
     * The webhook link is: https://{example.com}/myfatoorah/webhook
     */
    'webhook_secret_key' => (string) env('MYFATOORAH_WEBHOOK_SECRET', ''),
    /**
     * Register Apple Pay (boolean)
     * Set it to true to show the Apple Pay on the checkout page.
     * First, verify your domain with Apple Pay before you set it to true.
     * You can either follow the steps here: https://docs.myfatoorah.com/docs/apple-pay#verify-your-domain-with-apple-pay or contact the MyFatoorah support team (tech@myfatoorah.com).
     */
    'register_apple_pay' => (bool) env('MYFATOORAH_REGISTER_APPLE_PAY', false),
];
