<?php
// app/Http/Requests/StoreProductoRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    return [
        'nombre' => [
            'required', 'string', 'max:100',
            Rule::unique('productos')->ignore($this->route('producto'))
        ],
        'categoria_id' => 'required|exists:categorias,id',
        // Cambiamos decimal por numeric para que acepte tanto 10 como 10.555
        'kilogramos' => 'required|numeric|min:0.001', 
        'precio_compra' => 'required|numeric|min:0',
        'precio_venta_kg' => 'required|numeric|min:0',
        'proveedor_id' => 'required|exists:proveedores,id',
        'desperdicio' => 'nullable|numeric|min:0|lt:kilogramos',
        'detalle' => 'nullable|string|max:500'
    ];
}

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio',
            'nombre.unique' => 'Ya existe un producto con este nombre',
            'categoria_id.required' => 'La categoría es obligatoria',
            'kilogramos.required' => 'Los kilogramos son obligatorios',
            'kilogramos.min' => 'Los kilogramos deben ser mayores a 0',
            'precio_compra.required' => 'El precio de compra es obligatorio',
            'precio_venta_kg.required' => 'El precio de venta por kg es obligatorio',
            'proveedor_id.required' => 'El proveedor es obligatorio',
            'desperdicio.lt' => 'El desperdicio no puede ser mayor o igual a los kilogramos totales',
            'detalle.max' => 'El detalle no puede exceder los 500 caracteres'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del producto',
            'categoria_id' => 'categoría',
            'kilogramos' => 'kilogramos',
            'precio_compra' => 'precio de compra',
            'precio_venta_kg' => 'precio de venta por kg',
            'proveedor_id' => 'proveedor',
            'desperdicio' => 'desperdicio',
            'detalle' => 'detalle'
        ];
    }
}