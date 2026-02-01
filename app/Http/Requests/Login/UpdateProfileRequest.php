<?php

namespace App\Http\Requests\Login;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = auth()->id();

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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('current_password')) {
                $user = auth()->user();
                if (!Hash::check($this->current_password, $user->password)) {
                    $validator->errors()->add('current_password', 'La contraseña actual es incorrecta.');
                }
            }
        });
    }

    public function messages()
    {
        return [
            'nombre.unique' => 'El nombre de usuario ya está en uso',
            'current_password.required' => 'La contraseña actual es requerida',
            'new_password.min' => 'La nueva contraseña debe tener al menos 6 caracteres',
            'new_password.confirmed' => 'Las contraseñas no coinciden'
        ];
    }
}