<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'recsys' => [
        'fp_growth_url' => env('FP_GROWTH_API_URL', 'http://127.0.0.1:8003'),

        // LSTM
        'lstm_url' => env('FLASK_PRED_URL', 'http://127.0.0.1:5000'),
        'lstm_token' => env('PRED_TOKEN'),
        'lstm_look_back' => (int) env('LSTM_LOOK_BACK', 2),
        'lstm_model_version' => env('MODEL_VERSION', 'lstm_v1'),
        'profit_threshold_absolute' => env('PROFIT_THRESHOLD_ABSOLUTE'),
    ],
];
