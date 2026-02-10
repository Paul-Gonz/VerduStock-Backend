<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedoresCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 
                'string', 
                'max:255',
                // Blindaje: El nombre debe ser único, pero ignorando los que están en la papelera
                Rule::unique('proveedores', 'nombre')->whereNull('deleted_at')
            ],
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'detalle' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.unique' => 'Ya existe un proveedor activo con este nombre.',
            'nombre.max' => 'El nombre es demasiado largo.',
        ];
    }
}