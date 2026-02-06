<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'check-auth',
        'register',
        'usuarios',
        'usuarios*',  
        'proveedores',
        'proveedores/*',
        'categorias',
        'categorias/*',
        'productos*',
        'profile',
        'delete-account',
        'dashboard',
        'api-test'
    ],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => ['http://localhost:3000'], 
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true, 
];