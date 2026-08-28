<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayNexus API Keys
    |--------------------------------------------------------------------------
    |
    | Your PayNexus API keys. Obtain these from the PayNexus merchant dashboard
    | at https://paynexus.co.ke/merchant/merchant-api-keys
    |
    | Secret Key (sk_): Required for write operations (initiate payments, manage
    | webhooks). Must be kept server-side only.
    |
    | Public Key (pk_): Optional, for read operations only (merchant info, payment
    | status). Safe to use in client-side code. Enforced as read-only at the
    | middleware level.
    |
    */
    'secret_key' => env('PAYNEXUS_SECRET_KEY', env('PAYNEXUS_API_KEY', '')),
    'public_key' => env('PAYNEXUS_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | PayNexus Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the PayNexus API. Change only for staging or
    | self-hosted instances.
    |
    */
    'base_url' => env('PAYNEXUS_BASE_URL', 'https://paynexus.co.ke'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('PAYNEXUS_CURRENCY', 'KES'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | PayNexus will POST payment status updates to the path below.
    | The signature header (X-PayNexus-Signature) is verified using
    | the webhook secret you configure in the PayNexus dashboard.
    |
    */
    'webhook' => [
        'secret' => env('PAYNEXUS_WEBHOOK_SECRET', ''),
        'path' => env('PAYNEXUS_WEBHOOK_PATH', '/paynexus/webhook'),
        'tolerance' => 300, // seconds — reject payloads older than this
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Polling
    |--------------------------------------------------------------------------
    |
    | When you call PayNexus::pollStatus(), these settings control the
    | polling behaviour.
    |
    */
    'polling' => [
        'interval' => env('PAYNEXUS_POLL_INTERVAL', 3),        // seconds between polls
        'timeout' => env('PAYNEXUS_POLL_TIMEOUT', 120),       // max seconds to poll
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => env('PAYNEXUS_HTTP_TIMEOUT', 30),
        'retry_times' => env('PAYNEXUS_HTTP_RETRIES', 2),
        'retry_sleep' => env('PAYNEXUS_HTTP_RETRY_SLEEP', 500), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Set a channel name to send PayNexus SDK logs to a dedicated channel.
    | Leave null to use the default Laravel log channel.
    |
    */
    'log_channel' => env('PAYNEXUS_LOG_CHANNEL'),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for webhook processing. When enabled,
    | webhooks will be processed asynchronously via Laravel queues.
    |
    */
    'queue' => [
        'webhooks' => env('PAYNEXUS_QUEUE_WEBHOOKS', false),
        'connection' => env('PAYNEXUS_QUEUE_CONNECTION', 'default'),
        'queue' => env('PAYNEXUS_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for webhook jobs. Supports different
    | backoff strategies: linear, exponential, or constant.
    |
    */
    'retry' => [
        'webhook_max_attempts' => env('PAYNEXUS_WEBHOOK_MAX_ATTEMPTS', 3),
        'webhook_backoff' => env('PAYNEXUS_WEBHOOK_BACKOFF', 'exponential'),
        'webhook_base_delay' => env('PAYNEXUS_WEBHOOK_BASE_DELAY', 1000),
    ],
];
