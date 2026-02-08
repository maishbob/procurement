<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cookie Path
    |--------------------------------------------------------------------------
    |
    | This option determines the default path that will be available to the
    | cookie on the domain. By default, this will be '/' which will make
    | the cookie available to the entire domain.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Default Cookie Domain
    |--------------------------------------------------------------------------
    |
    | Here you may change the domain of the cookie that will be used by the
    | application. This will determine which domains the cookie is available
    | to in your application.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Default Cookie Security
    |--------------------------------------------------------------------------
    |
    | By default, cookies will only be sent back to the server if the browser
    | has an HTTPS connection. This keeps the cookie from being sent to you
    | when it can be intercepted by a third-party.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol.
    |
    */

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax".
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    'same_site' => 'lax',

];
