<?php

namespace App\Http\Requests\Login;

use Illuminate\Foundation\Http\FormRequest;
use App\Repositories\UsuarioRepository;

class RegisterRequest extends FormRequest
{
    protected $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        parent::__construct();
        $this->usuarioRepository = $usuarioRepository;
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                'min:3',
                function ($attribute, $value, $fail) {
                    if ($this->usuarioRepository->checkNombreExists($value)) {
                        $fail('El nombre de usuario ya está en uso.');
                    }
                }
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed'
            ]
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre de usuario es requerido',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres',
            'password.required' => 'La contraseña es requerida',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres'
        ];
    }
}