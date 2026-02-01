<?php

namespace App\Http\Controllers;

use App\Http\Requests\Login\RegisterRequest;
use App\Http\Requests\Login\UpdateProfileRequest;
use App\Http\Resources\UsuarioResource;
use App\Repositories\UsuarioRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class UsuarioController extends Controller
{
    protected $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    // API Methods
    public function index()
    {
        $usuarios = $this->usuarioRepository->paginate(15);
        return UsuarioResource::collection($usuarios);
    }

    public function store(RegisterRequest $request)
    {
        $usuario = $this->usuarioRepository->create($request->validated());
        
        Auth::login($usuario);
        $request->session()->regenerate();

        Log::info('Usuario registrado', [
            'user_id' => $usuario->id,
            'ip' => $request->ip()
        ]);
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json(["Registro exitoso"], 201);
    }
    
    // Para web, redirigir después del registro
    Auth::login($usuario);
    return redirect('/dashboard')->with('success', 'Registro exitoso');
}

    public function show($id)
    {
        $usuario = $this->usuarioRepository->find($id);
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return new UsuarioResource($usuario);
    }

    public function update(UpdateProfileRequest $request, $id)
    {
        $data = $request->validated();
        
        // Remove current_password from update data
        unset($data['current_password']);
        
        if (isset($data['new_password'])) {
            $data['password'] = $data['new_password'];
            unset($data['new_password']);
        }

        $usuario = $this->usuarioRepository->update($id, $data);
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        Log::info('Perfil actualizado', [
            'user_id' => $usuario->id,
            'ip' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente.',
            'data' => new UsuarioResource($usuario)
        ]);
    }

    public function profile()
    {
        $usuario = Auth::user();
        return new UsuarioResource($usuario);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $userId = Auth::id();
        
        unset($data['current_password']);
        
        if (isset($data['new_password'])) {
            $data['password'] = $data['new_password'];
            unset($data['new_password']);
        }

        $usuario = $this->usuarioRepository->update($userId, $data);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente.',
            'data' => new UsuarioResource($usuario)
        ]);
    }

    public function destroy($id, Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $usuario = $this->usuarioRepository->find($id);
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        if (!Hash::check($request->password, $usuario->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Contraseña incorrecta'
            ], 422);
        }

        if ($usuario->id === Auth::id()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $this->usuarioRepository->delete($id);

        Log::warning('Cuenta eliminada', [
            'deleted_user_id' => $id,
            'ip' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada exitosamente'
        ]);
    }

    // WEB Methods
    public function webIndex()
    {
        $usuarios = $this->usuarioRepository->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }

    public function webShow($id)
    {
        $usuario = $this->usuarioRepository->find($id);
        
        if (!$usuario) {
            abort(404);
        }

        return view('usuarios.show', compact('usuario'));
    }

    public function webProfile()
    {
        $usuario = Auth::user();
        return view('usuarios.profile', compact('usuario'));
    }

    public function webUpdateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $userId = Auth::id();
        
        unset($data['current_password']);
        
        if (isset($data['new_password'])) {
            $data['password'] = $data['new_password'];
            unset($data['new_password']);
        }

        $this->usuarioRepository->update($userId, $data);

        return back()->with('success', 'Perfil actualizado exitosamente.');
    }
}