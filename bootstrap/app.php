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
        // Quitamos statefulApi() y StartSession porque el Token viaja en el Header, no en la Cookie.
        
        $middleware->validateCsrfTokens(except: [
            'api/*', // Con Tokens, el CSRF ya no es necesario para la API
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();