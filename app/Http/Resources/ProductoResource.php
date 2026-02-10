<?php
// app/Http/Resources/ProductoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'categoria_id' => $this->categoria_id,
            // Blindaje: si la categoría no existe (fue borrada), devolvemos valores por defecto
            'categoria_nombre' => $this->categoria->nombre ?? 'Sin Categoría',
            'categoria_emoji' => $this->categoria->emoji ?? '📦',
            
            'kilogramos' => (float) $this->kilogramos,
            'detalle' => $this->detalle,
            'precio_compra' => (float) $this->precio_compra,
            'precio_venta_kg' => (float) $this->precio_venta_kg,
            
            'proveedor_id' => $this->proveedor_id,
            'proveedor_nombre' => $this->proveedor->nombre ?? 'Proveedor no disponible',
            
            'desperdicio' => (float) $this->desperdicio,
            'usuario_id' => $this->usuario_id,
            'usuario_nombre' => $this->usuario->nombre ?? 'Sistema',
            
            // Accesores (Asegúrate de que en el Modelo estos cálculos manejen nulos si es necesario)
            'kilogramos_netos' => (float) $this->kilogramos_netos,
            'precio_venta_total' => (float) $this->precio_venta_total,
            'margen_ganancia' => (float) $this->margen_ganancia,
            'ganancia_potencial' => (float) $this->ganancia_potencial,
            
            // Soft Delete Info
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
            
            // Timestamps protegidos contra nulos
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            
            // Links
            'links' => [
                'self' => route('productos.show', $this->id),
                'edit' => route('productos.edit', $this->id),
                'delete' => route('productos.destroy', $this->id)
            ]
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
            'version' => '1.0.0' // Opcional: útil para debuguear cambios en el front
        ];
    }
}