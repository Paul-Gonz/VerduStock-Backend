<?php

namespace App\Http\Requests\Login;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'remember' => 'sometimes|boolean'
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre de usuario es requerido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres'
        ];
    }
}