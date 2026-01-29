<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedoresController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('proveedores')->group(function () {
    Route::get('/', [ProveedoresController::class, 'index']);
    Route::post('/', [ProveedoresController::class, 'store']);
    Route::get('/{id}', [ProveedoresController::class, 'show']);
    Route::put('/{id}', [ProveedoresController::class, 'update']);
});
