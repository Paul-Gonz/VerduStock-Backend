<?php
// app/Http/Requests/Categoria/CategoriaUpdateRequest.php

namespace App\Http\Requests\Categoria;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Soporta tanto el parámetro {categoria} como {id} según tu ruta
        $categoriaId = $this->route('categoria') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('categorias', 'nombre')
                    ->ignore($categoriaId)
                    ->whereNull('deleted_at') // Ignora registros en la papelera
            ],
            'detalle' => 'sometimes|nullable|string',
            'emoji' => 'sometimes|nullable|string|max:16',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una categoría activa con este nombre.',
            'nombre.max' => 'El nombre no debe exceder los 100 caracteres.',
        ];
    }
}