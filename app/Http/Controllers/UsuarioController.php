<?php

namespace App\Http\Controllers;

use App\Http\Requests\Login\RegisterRequest;
use App\Http\Requests\Login\UpdateProfileRequest;
use App\Http\Requests\Login\UpdateUserRequest;
use App\Http\Resources\UsuarioResource;
use App\Repositories\UsuarioRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
    try {
        $usuario = $this->usuarioRepository->create($request->validated());
        
        Log::info('Usuario registrado', [
            'user_id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'ip' => $request->ip(),
            'created_by' => Auth::id() 
        ]);

        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'created_at' => $usuario->created_at
                ]
            ], 201);
        }
        
        return redirect()->route('usuarios.index') // o la ruta que prefieras
                        ->with('success', 'Usuario creado exitosamente');
        
    } catch (\Exception $e) {
        Log::error('Error al registrar usuario: ' . $e->getMessage(), [
            'request' => $request->all(),
            'ip' => $request->ip()
        ]);
        
        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
        
        return back()->with('error', 'Error al registrar usuario');
    }
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

public function update(UpdateUserRequest $request, $id)
{
    $usuario = $this->usuarioRepository->find($id);
    
    if (!$usuario) {
        return response()->json([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ], 404);
    }

    // DEBUG: Verifica la contraseña que llega
    Log::debug('Contraseña recibida para validación', [
        'usuario_id' => $usuario->id,
        'password_plain' => $request->current_password,
        'password_hash_en_db' => $usuario->password
    ]);

    // Verificar la contraseña del usuario que se va a editar
    if (!Hash::check($request->current_password, $usuario->password)) {
        Log::error('Error en validación de contraseña', [
            'usuario_id' => $usuario->id,
            'hash_check_result' => Hash::check($request->current_password, $usuario->password)
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'La contraseña del usuario es incorrecta'
        ], 422);
    }

    $data = $request->validated();
    
    // Remove current_password from update data
    unset($data['current_password']);
    
    if (isset($data['new_password'])) {
        // NO encriptes aquí, deja que el Repository lo haga
        $data['password'] = $data['new_password']; // Pasa el texto plano
        unset($data['new_password']);
    }

    $usuarioActualizado = $this->usuarioRepository->update($id, $data);
    
    if (!$usuarioActualizado) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar usuario'
        ], 500);
    }

    Log::info('Usuario actualizado por administrador', [
        'user_id' => $usuario->id,
        'updated_by' => Auth::id(),
        'ip' => $request->ip()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Usuario actualizado exitosamente.',
        'data' => new UsuarioResource($usuarioActualizado)
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
    
    // Verificar la contraseña actual del usuario logueado
    $user = Auth::user();
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Tu contraseña actual es incorrecta'
        ], 422);
    }
    
    unset($data['current_password']);
    
    if (isset($data['new_password'])) {
        $data['password'] = bcrypt($data['new_password']);
        unset($data['new_password']);
    }

    $usuario = $this->usuarioRepository->update($userId, $data);

    Log::info('Perfil actualizado por usuario', [
        'user_id' => $userId,
        'ip' => $request->ip()
    ]);

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

    // DEBUG
    Log::debug('Eliminación - verificación de contraseña', [
        'usuario_id' => $usuario->id,
        'password_provided' => $request->password,
        'hash_check' => Hash::check($request->password, $usuario->password)
    ]);

    // Verificar si el usuario intenta eliminarse a sí mismo
    if ($usuario->id === Auth::id()) {
        return response()->json([
            'success' => false,
            'message' => 'No puedes eliminar tu propia cuenta desde aquí. Usa la opción "Eliminar mi cuenta" en tu perfil.'
        ], 403);
    }

    // Verificar contraseña del usuario que se va a eliminar
    if (!Hash::check($request->password, $usuario->password)) {
        return response()->json([
            'success' => false,
            'message' => 'La contraseña del usuario es incorrecta'
        ], 422);
    }

    // Eliminar el usuario
    $this->usuarioRepository->delete($id);

    $currentUser = Auth::user();
    
    Log::warning('Cuenta eliminada por administrador', [
        'deleted_user_id' => $id,
        'deleted_user_name' => $usuario->nombre,
        'deleted_by' => $currentUser->id,
        'deleted_by_name' => $currentUser->nombre,
        'ip' => $request->ip()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Usuario eliminado exitosamente'
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

    /*public function deleteMyAccount(Request $request)
{
    $request->validate([
        'password' => 'required|string',
        'confirmation' => 'required|string|in:ELIMINAR'
    ]);

    $user = Auth::user();
    
    // Verificar contraseña
    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Contraseña incorrecta'
        ], 422);
    }

    // Guardar información del usuario antes de eliminarlo
    $userId = $user->id;
    $userName = $user->nombre;
    
    // Cerrar sesión primero
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    // Eliminar la cuenta
    $this->usuarioRepository->delete($userId);

    Log::warning('Cuenta auto-eliminada', [
        'user_id' => $userId,
        'user_name' => $userName,
        'ip' => $request->ip()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Tu cuenta ha sido eliminada exitosamente. ¡Esperamos verte de nuevo pronto!'
    ]);
}*/
}