<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gateway API Key
    |--------------------------------------------------------------------------
    |
    | API key used to protect the gateway from unauthorized access.
    | Clients must provide this key via the X-Api-Key header or
    | api_key query parameter.
    |
    */
    'api_key' => env('GATEWAY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | SIASN Base Configuration
    |--------------------------------------------------------------------------
    */
    'siasn_url' => env('SIASN_URL', 'https://apimws.bkn.go.id:8243/apisiasn/1.0'),
    'timeout' => env('SIASN_TIMEOUT', 60),
    'verify_ssl' => env('SIASN_VERIFY_SSL', true),

];
