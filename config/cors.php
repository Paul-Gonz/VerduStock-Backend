<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // Usamos '*' para que todas las rutas permitan CORS con credenciales
    'paths' => ['*', 'api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://verdu-stock-frontend.vercel.app',
        'https://verdu-stock-frontend-git-main-paul-gonzs-projects.vercel.app',
        'http://localhost:3000', // No lo borres, por si necesitas volver a local
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];