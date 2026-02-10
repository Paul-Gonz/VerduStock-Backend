<?php
// app/Http/Requests/ProductoCreateRequest.php

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
                // Blindaje: Único solo entre productos no eliminados
                Rule::unique('productos')->ignore($this->route('id') ?? $this->route('producto'))->whereNull('deleted_at')
            ],
            'categoria_id' => [
                'required',
                // Blindaje: La categoría debe existir y NO estar eliminada
                Rule::exists('categorias', 'id')->whereNull('deleted_at')
            ],
            'kilogramos' => 'required|numeric|min:0.001', 
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta_kg' => 'required|numeric|min:0',
            'proveedor_id' => [
                'required',
                // Blindaje: El proveedor debe existir y NO estar eliminado
                Rule::exists('proveedores', 'id')->whereNull('deleted_at')
            ],
            'desperdicio' => 'nullable|numeric|min:0|lt:kilogramos',
            'detalle' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio',
            'nombre.unique' => 'Ya existe un producto activo con este nombre',
            'categoria_id.required' => 'La categoría es obligatoria',
            'categoria_id.exists' => 'La categoría seleccionada no es válida o ha sido eliminada',
            'kilogramos.required' => 'Los kilogramos son obligatorios',
            'kilogramos.min' => 'Los kilogramos deben ser mayores a 0',
            'precio_compra.required' => 'El precio de compra es obligatorio',
            'precio_venta_kg.required' => 'El precio de venta por kg es obligatorio',
            'proveedor_id.required' => 'El proveedor es obligatorio',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido o ha sido eliminado',
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