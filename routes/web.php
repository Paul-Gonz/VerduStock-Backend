<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;

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

// 🔹 RUTAS DE CATEGORÍAS (las que ya tienes)
Route::prefix('categorias')->group(function () {
    Route::get('/', [CategoriaController::class, 'index']);
    Route::post('/', [CategoriaController::class, 'store']);
    Route::get('/{id}', [CategoriaController::class, 'show']);
    Route::put('/{id}', [CategoriaController::class, 'update']);
    //Route::delete('/{id}', [CategoriaController::class, 'destroy']);
});

// 🔹 NUEVAS RUTAS DE PRODUCTOS (CRUD completo)
Route::prefix('productos')->group(function () {
    // CRUD básico
    Route::get('/', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/crear', [ProductoController::class, 'create'])->name('productos.create');
    Route::post('/', [ProductoController::class, 'store'])->name('productos.store');
    Route::get('/{id}', [ProductoController::class, 'show'])->name('productos.show');
    Route::get('/{id}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
    Route::put('/{id}', [ProductoController::class, 'update'])->name('productos.update');
    Route::delete('/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy');
    
    // Rutas adicionales
    Route::get('/reporte/estadisticas', [ProductoController::class, 'reporte'])->name('productos.reporte');
    Route::get('/categoria/{categoriaId}', [ProductoController::class, 'porCategoria'])->name('productos.por-categoria');
    Route::get('/alto-desperdicio', [ProductoController::class, 'altoDesperdicio'])->name('productos.alto-desperdicio');
    
  });

    //Login/Logout

// 🔹 RUTAS PÚBLICAS DE AUTENTICACIÓN
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/check-auth', [AuthController::class, 'checkAuth']);

// 🔹 RUTAS PROTEGIDAS (con middleware de sesión)
Route::middleware(['auth'])->group(function () {
    
    // 🔹 RUTAS DE USUARIOS (CRUD completo - protegido)
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/{id}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::put('/{id}', [UsuarioController::class, 'update']);
        Route::delete('/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
        //Route::delete('/mi-cuenta/eliminar', [UsuarioController::class, 'deleteMyAccount']);
         Route::get('/profile/me', [UsuarioController::class, 'profile'])->name('usuarios.profile');
        Route::put('/profile/update', [UsuarioController::class, 'updateProfile'])->name('usuarios.update-profile');
        // Rutas de perfil
    });

    // 🔹 Dashboard
    Route::get('/dashboard', function () {
        return response()->json([
            'success' => true,
            'message' => 'Bienvenido al dashboard',
            'data' => [
                'user' => auth()->user()->only(['id', 'nombre'])
            ]
        ]);
    })->name('dashboard');
});

Route::get('/login-form', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::get('/register-form', [AuthController::class, 'showRegisterForm'])->name('register.form');
