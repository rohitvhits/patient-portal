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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'erp' => [
        'base_url' => env('ERP_API_BASE_URL', 'http://localhost/nybesterp/public/api/portal'),
        'token' => env('ERP_API_TOKEN'),
    ],

    /*
    | TEMPORARY — staging/testing only. When set, this exact 6-digit code always
    | verifies successfully without calling the ERP, so QA can log in on servers
    | that don't have live Twilio credentials to receive the real SMS code.
    | Leave OTP_BYPASS_CODE unset in production. Remove this block once staging
    | has real Twilio credentials.
    */
    'otp_bypass_code' => env('OTP_BYPASS_CODE'),

];
