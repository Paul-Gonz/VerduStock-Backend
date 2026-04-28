<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Configuración de CORS integrada en Laravel 12
        $middleware->validateCsrfTokens(except: [
            'login',
            'logout',
            'api/*',
        ]);

        $middleware->statefulApi(); // Si usas Sanctum

        // Configura el CORS aquí directamente
        $middleware->trustProxies(at: '*');
        
        // Esta es la parte que te está fallando:
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();