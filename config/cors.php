<?php

return [
    // Usamos '*' para que cualquier ruta nueva que crees funcione sin volver aquí
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000', 
        'https://verdu-stock-frontend.vercel.app',
        'https://verdu-stock-frontend-git-main-paul-gonzs-projects.vercel.app', 
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Aunque uses Tokens, déjalo en true por si alguna vez mandas headers personalizados
    'supports_credentials' => true, 
];