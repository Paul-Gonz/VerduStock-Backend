<?php
// app/Http/Requests/ProductoUpdateRequest.php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductoUpdateRequest extends ProductoCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        // Detectamos el ID ya sea que venga como 'producto' o como 'id' en la ruta
        $productoId = $this->route('producto') ?? $this->route('id');

        // En actualización, permitimos que las reglas sean 'sometimes' para ediciones parciales
        foreach ($rules as $key => $value) {
            if (is_string($value)) {
                $rules[$key] = 'sometimes|' . $value;
            } elseif (is_array($value)) {
                array_unshift($rules[$key], 'sometimes');
            }
        }

        // Ajustamos la regla unique para que ignore el registro actual y los eliminados
        $rules['nombre'] = [
            'sometimes',
            'required',
            'string',
            'max:100',
            Rule::unique('productos', 'nombre')
                ->ignore($productoId)
                ->whereNull('deleted_at') // Blindaje contra registros en papelera
        ];
        
        return $rules;
    }
}