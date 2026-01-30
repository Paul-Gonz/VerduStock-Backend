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
        $categoriaId = $this->route('categoria') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('categorias', 'nombre')->ignore($categoriaId)
            ],
            'detalle' => 'sometimes|nullable|string'
        ];
    }
}