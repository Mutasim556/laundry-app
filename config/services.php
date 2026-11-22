<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'uk_address' => [
        'provider' => env('UK_ADDRESS_PROVIDER', 'auto'), // 'ideal_postcodes', 'getaddress', 'nominatim', or 'auto'
        'ideal_postcodes_key' => env('IDEAL_POSTCODES_API_KEY'),
        'getaddress_key' => env('GETADDRESS_API_KEY'),
        'postcodes_io_url' => env('POSTCODES_IO_URL', 'https://api.postcodes.io'),
        'nominatim_url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'gbp'),
    ],

];
