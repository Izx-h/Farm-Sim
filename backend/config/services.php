<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'open_meteo' => [
        'base_url' => env('OPEN_METEO_BASE', 'https://api.open-meteo.com/v1'),
    ],

    'market_data' => [
        'api_key' => env('MARKET_DATA_API_KEY'),
        'api_url' => env('MARKET_DATA_API_URL'),
    ],

];
