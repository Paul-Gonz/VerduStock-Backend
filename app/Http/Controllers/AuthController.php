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
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demasiados intentos. Intente nuevamente en ' . $seconds . ' segundos.'
                ], 429);
            }
            
            throw ValidationException::withMessages([
                'nombre' => "Demasiados intentos. Intente nuevamente en {$seconds} segundos."
            ]);
        }

        // Credenciales simplificadas
        $credentials = $request->only('nombre', 'password');
        
        // Intento de autenticación estándar
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            
            $request->session()->regenerate();
            $user = Auth::user();
            
            Log::info('Login exitoso', [
                'user_id' => $user->id,
                'nombre' => $user->nombre,
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'nombre' => $user->nombre
                        ]
                    ]
                ]);
            }

            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($throttleKey, 60);
        
        Log::warning('Login fallido', [
            'nombre' => $request->nombre,
            'ip' => $request->ip()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        throw ValidationException::withMessages([
            'nombre' => 'Credenciales incorrectas'
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            Log::info('Logout', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ]);
        }

        return redirect('/')->with('info', 'Sesión cerrada exitosamente');
    }

    public function checkAuth(Request $request)
    {
        $user = Auth::user();
        $isAuthenticated = Auth::check();
        
        $response = [
            'success' => true,
            'authenticated' => $isAuthenticated
        ];
        
        if ($user) {
            $response['user'] = [
                'id' => $user->id,
                'nombre' => $user->nombre
            ];
        }

        return response()->json($response);
    }

    // WEB Methods
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }
}