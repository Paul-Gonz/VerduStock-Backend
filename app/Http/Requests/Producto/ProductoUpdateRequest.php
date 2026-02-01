<?php
// app/Http/Requests/UpdateProductoRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductoUpdateRequest extends ProductoCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        // En actualización, el nombre puede ser el mismo para este producto
        $rules['nombre'] = [
            'required',
            'string',
            'max:100',
            Rule::unique('productos')->ignore($this->route('producto'))
        ];
        
        return $rules;
    }
}