<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as PesaPal, M-Pesa, Mailgun, Postmark, AWS and more. This file provides
    | the de facto location for this type of information.
    |
    */

    'pesapal' => [
        'base_url' => env('PESAPAL_BASE_URL', 'https://cybqa.pesapal.com/pesapalv3'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY', ''),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET', ''),
        'callback_url' => env('PESAPAL_CALLBACK_URL', env('APP_URL') . '/api/pesapal/callback'),
        'ipn_id' => env('PESAPAL_IPN_ID', ''),
        'enabled' => env('PESAPAL_ENABLED', false),
    ],

    'mpesa' => [
        'consumer_key' => env('MPESA_CONSUMER_KEY', ''),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),
        'shortcode' => env('MPESA_SHORTCODE', ''),
        'passkey' => env('MPESA_PASSKEY', ''),
        'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL') . '/api/mpesa/callback'),
        'enabled' => env('MPESA_ENABLED', false),
    ],

    'africastalking' => [
        'api_key'   => env('AFRICASTALKING_API_KEY', ''),
        'username'  => env('AFRICASTALKING_USERNAME', 'sandbox'),
        'sender_id' => env('AFRICASTALKING_SENDER_ID', ''),
        // Override to https://api.sandbox.africastalking.com/version1/messaging for sandbox testing
        'api_url'   => env('AFRICASTALKING_API_URL', 'https://api.africastalking.com/version1/messaging'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
        'auth_token'  => env('TWILIO_AUTH_TOKEN', ''),
        'from'        => env('TWILIO_FROM', ''),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'africastalking'),
    ],

];
