<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // 'allowed_methods' => ['*'],

    // 'allowed_origins' => ['*'],

    // 'allowed_origins_patterns' => [],

    // 'allowed_headers' => ['*'],

    // 'exposed_headers' => [],

    // 'max_age' => 0,

    // 'supports_credentials' => false,

    // config/cors.php


    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],

    // AQUÍ EL CAMBIO CLAVE:
     'allowed_origins' => [
        'https://alianzaepaecciledna.edu.gt',       // Tu dominio principal (Producción)
        'https://www.alianzaepaecciledna.edu.gt',   // Es bueno agregar la versión www también
        'http://localhost:5173',                    // Puedes dejarlo si a veces conectas tu PC local a este server
    ],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,

    // Y ESTE TAMBIÉN EN TRUE:
    'supports_credentials' => false,


];
