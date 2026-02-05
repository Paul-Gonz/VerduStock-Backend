<?php

namespace App\Http\Requests\Login;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('id');

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                'min:3',
                Rule::unique('usuarios')->ignore($userId)
            ],
            'current_password' => 'required|string',
            'new_password' => 'nullable|string|min:6|confirmed'
        ];
    }

    public function messages()
    {
        return [
            'nombre.unique' => 'El nombre de usuario ya está en uso',
            'current_password.required' => 'La contraseña actual del usuario es requerida',
            'new_password.min' => 'La nueva contraseña debe tener al menos 6 caracteres',
            'new_password.confirmed' => 'Las contraseñas no coinciden'
        ];
    }
}