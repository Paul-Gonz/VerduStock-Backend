<?php

namespace App\Http\Controllers;

use App\Http\Requests\Login\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $throttleKey = 'login:' . $request->ip() . ':' . strtolower($request->nombre);
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Intente en {$seconds} segundos."
            ], 429);
        }

        $credentials = $request->only('nombre', 'password');
        
        // Intentamos autenticar
        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);
            
            $user = Auth::user();

            // Creamos el token de Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Login exitoso (Token)', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'token' => $token, // Enviamos el token al frontend
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'nombre' => $user->nombre
                    ]
                ]
            ]);
        }

        RateLimiter::hit($throttleKey, 60);
        
        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas'
        ], 401);
    }

    public function logout(Request $request)
    {
        $user = $request->user(); // Obtenemos el usuario por el token
        
        if ($user) {
            // Eliminamos el token actual para cerrar la sesión
            $user->currentAccessToken()->delete();

            Log::info('Logout (Token)', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Token revocado exitosamente'
        ]);
    }

    public function checkAuth(Request $request)
    {
        // Si el middleware 'auth:sanctum' deja pasar la petición, 
        // el usuario ya está autenticado.
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre
            ]
        ]);
    }
}