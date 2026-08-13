<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This project has a single kind of user — patients — authenticated by
    | mobile number + OTP (see App\Http\Controllers\Auth\AuthController).
    | There is no password-based login and no staff/admin guard.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'patient'),
        'passwords' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'patient' => [
            'driver' => 'session',
            'provider' => 'patient_users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'patient_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\PatientUser::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Not used — patients authenticate via OTP, not passwords.
    |
    */

    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
