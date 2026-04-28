<?php

return [
    // Agregamos '*' al final de paths por si acaso, pero mantenemos los específicos
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000', 
        'https://verdu-stock-frontend.vercel.app',
    ],

    // ESTA ES LA CLAVE: Acepta cualquier URL de Vercel de tu proyecto
    'allowed_origins_patterns' => [
        '/^https:\/\/verdu-stock-frontend.*\.vercel\.app$/',
    ],

    // Permitimos todos los headers para que el 'Authorization' pase sin rollos
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | supports_credentials en 'false' es lo correcto para Bearer Tokens.
    | Esto evita que el navegador se ponga paranoico con las cookies.
    */
    'supports_credentials' => false, 
];