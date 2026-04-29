<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Reportes\ReporteController;

Route::get('/', function () {
    return view('welcome');
});

// Ruta de prueba pública
Route::get('/public-test', function () {
    return response()->json(['status' => 'API Online y Pública']);
});

// 🔹 AUTENTICACIÓN
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/check-auth', [AuthController::class, 'checkAuth']);

Route::middleware(['auth:sanctum'])->group(function () {
    
    // 🔹 DASHBOARD
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // 🔹 PRODUCTOS (Corregido el orden para evitar Error 500)
    Route::prefix('productos')->group(function () {
        // Especiales
        Route::get('/trashed', [ProductoController::class, 'trashed'])->name('productos.trashed');
        Route::get('/reporte/estadisticas', [ProductoController::class, 'reporte'])->name('productos.reporte');
        Route::get('/alto-desperdicio', [ProductoController::class, 'altoDesperdicio'])->name('productos.alto-desperdicio');
        
        // CRUD
        Route::get('/', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/', [ProductoController::class, 'store'])->name('productos.store');
        
        // El orden aquí es vital: ID al final
        Route::get('/{id}', [ProductoController::class, 'show'])->name('productos.show');
        Route::get('/{id}/editar', [ProductoController::class, 'edit'])->name('productos.edit'); // <--- AQUÍ ESTÁ EL ERROR
        Route::put('/{id}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy');
        
        // Acciones Papelera
        Route::post('/{id}/restore', [ProductoController::class, 'restore'])->name('productos.restore');
        Route::delete('/{id}/force', [ProductoController::class, 'forceDelete'])->name('productos.force-delete');
        Route::get('/exportar/excel', [ProductoController::class, 'exportarExcel'])->name('productos.exportar.excel');
    });

    // 🔹 CATEGORÍAS
    Route::prefix('categorias')->group(function () {
        Route::get('/', [CategoriaController::class, 'index']);
        Route::get('/trashed', [CategoriaController::class, 'trashed']);
        Route::post('/', [CategoriaController::class, 'store']);
        Route::get('/{id}', [CategoriaController::class, 'show']);
        Route::put('/{id}', [CategoriaController::class, 'update']);
        Route::delete('/{id}', [CategoriaController::class, 'destroy']);
        Route::post('/{id}/restore', [CategoriaController::class, 'restore']);
        Route::delete('/{id}/force', [CategoriaController::class, 'forceDelete']);
    });

    // 🔹 PROVEEDORES
    Route::prefix('proveedores')->group(function () {
        Route::get('/', [ProveedoresController::class, 'index']);
        Route::get('/trashed', [ProveedoresController::class, 'trashed']);
        Route::post('/', [ProveedoresController::class, 'store']);
        Route::get('/{id}', [ProveedoresController::class, 'show']);
        Route::put('/{id}', [ProveedoresController::class, 'update']);
        Route::delete('/{id}', [ProveedoresController::class, 'destroy']);
        Route::post('/{id}/restore', [ProveedoresController::class, 'restore']);
        Route::delete('/{id}/force', [ProveedoresController::class, 'forceDelete']);
    });

    // 🔹 USUARIOS
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/profile/me', [UsuarioController::class, 'profile'])->name('usuarios.profile');
        Route::put('/profile/update', [UsuarioController::class, 'updateProfile'])->name('usuarios.update-profile');
        Route::get('/{id}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::put('/{id}', [UsuarioController::class, 'update']);
        Route::delete('/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    });

    //  REPORTES
    Route::prefix('reportes')->group(function () {
        // PDF Reports
        Route::get('/inventario-completo', [ReporteController::class, 'inventarioCompleto']);
        Route::get('/stock-bajo', [ReporteController::class, 'stockBajo']);
        Route::get('/reporte-desperdicios', [ReporteController::class, 'reporteDesperdicios']);
        Route::get('/analisis-rentabilidad', [ReporteController::class, 'analisisRentabilidad']);
        Route::get('/test-pdf', [ReporteController::class, 'testPdf']);

        // Excel/CSV Reports (NUEVAS RUTAS - IMPORTANTE)
        Route::get('/inventario-completo/excel', [ReporteController::class, 'inventarioCompletoExcel']);
        Route::get('/stock-bajo/excel', [ReporteController::class, 'stockBajoExcel']);
        Route::get('/reporte-desperdicios/excel', [ReporteController::class, 'reporteDesperdiciosExcel']);
        Route::get('/analisis-rentabilidad/excel', [ReporteController::class, 'analisisRentabilidadExcel']);
    });

});