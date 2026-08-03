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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'bpjs' => [
        'consumer_id' => env('BPJS_CONS_ID'),
        'secret_key' => env('BPJS_SECRET_KEY'),

        'antrol' => [
            'base_url' => env('BPJS_BASE_URL_ANTROL'),
            'user_key' => env('BPJS_USERKEY_ANTROL'),
            'connect_timeout' => (int) env('BPJS_ANTROL_CONNECT_TIMEOUT', 3),
            'timeout' => (int) env('BPJS_ANTROL_TIMEOUT', 8),
            'database_logging' => (bool) env('BPJS_DATABASE_LOGGING', true),
        ],

        'vclaim' => [
            'base_url' => env('BPJS_BASE_URL_VCLAIM'),
            'user_key' => env('BPJS_USERKEY_VCLAIM'),
            'connect_timeout' => (int) env('BPJS_VCLAIM_CONNECT_TIMEOUT', 3),
            'timeout' => (int) env('BPJS_VCLAIM_TIMEOUT', 8),
            'circuit_breaker_seconds' => (int) env(
                'BPJS_VCLAIM_CIRCUIT_BREAKER_SECONDS',
                30
            ),
            'database_logging' => (bool) env('BPJS_DATABASE_LOGGING', true),
        ],
    ],

    'radiology' => [
        'image_base_url' => env(
            'RADIOLOGY_IMAGE_BASE_URL',
            'http://192.168.2.9/webapps/radiologi'
        ),
        'connect_timeout' => (int) env(
            'RADIOLOGY_IMAGE_CONNECT_TIMEOUT',
            5
        ),
        'timeout' => (int) env('RADIOLOGY_IMAGE_TIMEOUT', 15),
    ],

];
