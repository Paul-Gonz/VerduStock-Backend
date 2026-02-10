<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedoresUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Capturamos el ID del proveedor desde la ruta
        $proveedorId = $this->route('id') ?? $this->route('proveedor');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:255',
                // Regla de unicidad inteligente:
                Rule::unique('proveedores', 'nombre')
                    ->ignore($proveedorId) // Ignora al proveedor que estamos editando
                    ->whereNull('deleted_at') // Solo compara contra proveedores NO eliminados
            ],
            'telefono' => 'sometimes|nullable|string|max:50',
            'direccion' => 'sometimes|nullable|string|max:255',
            'detalle' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe otro proveedor activo con este nombre.',
            'nombre.max' => 'El nombre no debe exceder los 255 caracteres.',
        ];
    }
}