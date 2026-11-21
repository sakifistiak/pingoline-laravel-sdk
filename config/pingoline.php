<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PingoLine API Key
    |--------------------------------------------------------------------------
    |
    | Your official API Token generated from the PingoLine portal dashboard
    | (https://pingoline.com/portal/developers).
    |
    */

    'api_key' => env('PINGOLINE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | PingoLine Base API URL
    |--------------------------------------------------------------------------
    |
    | Base endpoint for the PingoLine REST API. Default: https://pingoline.com/api/v1
    |
    */

    'api_url' => env('PINGOLINE_API_URL', 'https://pingoline.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The secret key used to verify HMAC-SHA256 signatures for incoming
    | webhook events delivered by PingoLine.
    |
    */

    'webhook_secret' => env('PINGOLINE_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP request timeout in seconds when communicating with PingoLine API.
    |
    */

    'timeout' => (int) env('PINGOLINE_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Automatic retry attempts on transient network or connection drops.
    |
    */

    'retry' => [
        'times' => (int) env('PINGOLINE_RETRY_TIMES', 2),
        'sleep' => (int) env('PINGOLINE_RETRY_SLEEP_MS', 200),
    ],

];
