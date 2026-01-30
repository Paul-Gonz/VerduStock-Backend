<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\CategoriaController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api-test', function () {
    return response()->json([
        'status' => 'Conectado',
        'message' => 'Nuxt 4 está hablando con Laravel',
        'time' => now()
    ]);
});

Route::prefix('proveedores')->group(function () {
    Route::get('/', [ProveedoresController::class, 'index']);
    Route::post('/', [ProveedoresController::class, 'store']);
    Route::get('/{id}', [ProveedoresController::class, 'show']);
    Route::put('/{id}', [ProveedoresController::class, 'update']);
});

Route::prefix('categorias')->group(function () {
    Route::get('/', [CategoriaController::class, 'index']);
    Route::post('/', [CategoriaController::class, 'store']);
    Route::get('/{id}', [CategoriaController::class, 'show']);
    Route::put('/{id}', [CategoriaController::class, 'update']);
    //Route::delete('/{id}', [CategoriaController::class, 'destroy']);
});
