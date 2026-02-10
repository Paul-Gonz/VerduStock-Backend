<?php
// app/Http/Requests/Categoria/CategoriaCreateRequest.php

namespace App\Http\Requests\Categoria;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Importante para usar reglas complejas

class CategoriaCreateRequest extends FormRequest
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
                'max:100',
                // Modificación: Único solo entre los registros que NO tienen deleted_at
                Rule::unique('categorias', 'nombre')->whereNull('deleted_at')
            ],
            'detalle' => 'nullable|string',
            'emoji' => 'nullable|string|max:16',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.max' => 'El nombre no debe exceder los 100 caracteres.',
            'nombre.unique' => 'Ya existe una categoría activa con este nombre.'
        ];
    }
}