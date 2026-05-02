<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'mensaje' => 'Bienvenido a la API de VerduStock (Entorno Local)',
        'estado' => 'En ejecución',
        'tecnologias' => ['Laravel 12', 'PHP 8.4'],
        'autor' => 'LoopInf'
    ]);
});