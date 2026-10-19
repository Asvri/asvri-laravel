<?php

// ASVRI for Laravel. Keys come from .env; nothing secret belongs in this file.
return [

    // Your secret key from ASVRI → Settings → API access: sk_test_… while you build, sk_live_… to take real money.
    'secret_key' => env('ASVRI_SECRET_KEY'),

    'webhook' => [
        // The signing secret of your webhook endpoint (whsec_…). `php artisan asvri:webhook` registers the endpoint
        // and prints it.
        'secret' => env('ASVRI_WEBHOOK_SECRET'),

        // Where ASVRI posts events. Set to null to register your own route instead.
        'path' => env('ASVRI_WEBHOOK_PATH', 'asvri/webhook'),

        // Middleware for that route. No session or CSRF: ASVRI's signature is what proves a request is genuine.
        'middleware' => [],

        // Seconds an event may be old before it is refused as a possible replay (0 = no check).
        'tolerance' => (int) env('ASVRI_WEBHOOK_TOLERANCE', 300),

        // ASVRI retries a delivery it isn't sure arrived, with the same event id. Remember handled ids (in the
        // cache, for this many seconds) so your listeners see each event once. 0 = let every delivery through.
        'dedupe_for' => (int) env('ASVRI_WEBHOOK_DEDUPE_FOR', 86400),
    ],

    // Connection settings; the defaults suit production.
    'api_base' => env('ASVRI_API_BASE', 'https://api.asvri.com/app/api/v1'),
    'api_ext' => env('ASVRI_API_EXT', ''),
    'timeout' => (int) env('ASVRI_TIMEOUT', 30),
    'max_retries' => (int) env('ASVRI_MAX_RETRIES', 2),
];
