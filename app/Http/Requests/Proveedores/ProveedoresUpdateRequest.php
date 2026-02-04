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
        $ProveedorId = $this->route('id');

        return [
            'nombre' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|nullable|string|max:50',
            'direccion' => 'sometimes|nullable|string|max:255',
            'detalle' => 'sometimes|nullable|string',
        ];
    }
}