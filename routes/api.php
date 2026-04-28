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

// 🔹 AUTENTICACIÓN PÚBLICA
Route::post('/login', [AuthController::class, 'login']);

// 🔹 RUTAS PROTEGIDAS POR SANCTUM (TOKEN)
// Cambiamos 'auth' por 'auth:sanctum'
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Mover Logout y Check-Auth adentro, porque requieren saber quién es el usuario vía Token
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/check-auth', [AuthController::class, 'checkAuth']);
    
    // 🔹 DASHBOARD
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // 🔹 PRODUCTOS
    Route::prefix('productos')->group(function () {
        Route::get('/trashed', [ProductoController::class, 'trashed'])->name('productos.trashed');
        Route::get('/reporte/estadisticas', [ProductoController::class, 'reporte'])->name('productos.reporte');
        Route::get('/alto-desperdicio', [ProductoController::class, 'altoDesperdicio'])->name('productos.alto-desperdicio');
        
        Route::get('/', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/', [ProductoController::class, 'store'])->name('productos.store');
        
        Route::get('/{id}', [ProductoController::class, 'show'])->name('productos.show');
        Route::get('/{id}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/{id}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy');
        
        Route::post('/{id}/restore', [ProductoController::class, 'restore'])->name('productos.restore');
        Route::delete('/{id}/force', [ProductoController::class, 'forceDelete'])->name('productos.force-delete');
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

    // 🔹 REPORTES
    Route::prefix('reportes')->group(function () {
        Route::get('/preview-inventario', [ReporteController::class, 'previewInventarioCompleto']);
        Route::get('/preview-rentabilidad', [ReporteController::class, 'previewAnalisisRentabilidad']);
        Route::get('/inventario-completo', [ReporteController::class, 'inventarioCompleto']);
        Route::get('/stock-bajo', [ReporteController::class, 'stockBajo']);
        Route::get('/reporte-desperdicios', [ReporteController::class, 'reporteDesperdicios']);
        Route::get('/analisis-rentabilidad', [ReporteController::class, 'analisisRentabilidad']);
    });
});