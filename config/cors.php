<?php

return [
    // Solo las rutas necesarias para evitar huecos de seguridad
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000', 
        'https://verdu-stock-frontend.vercel.app',
        // Esta es la URL de preview de Vercel, está bien dejarla
        'https://verdu-stock-frontend-git-main-paul-gonzs-projects.vercel.app', 
    ],

    'allowed_origins_patterns' => [],

    // Permitimos todos los headers porque necesitamos el 'Authorization' para el Token
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | Importante: Lo ponemos en 'false' porque estamos usando Bearer Tokens.
    | Si está en 'true', el navegador exige configuraciones de CORS mucho más
    | rígidas que suelen fallar en despliegues como Render/Vercel.
    */
    'supports_credentials' => false, 
];