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
            'categoria_nombre' => $this->categoria->nombre ?? null,
            'kilogramos' => (float) $this->kilogramos,
            'detalle' => $this->detalle,
            'precio_compra' => (float) $this->precio_compra,
            'precio_venta_kg' => (float) $this->precio_venta_kg,
            'proveedor_id' => $this->proveedor_id,
            'proveedor_nombre' => $this->proveedor->nombre ?? null,
            'desperdicio' => (float) $this->desperdicio,
            'usuario_id' => $this->usuario_id,
            'usuario_nombre' => $this->usuario->nombre ?? null,
            
            // Accesores
            'kilogramos_netos' => (float) $this->kilogramos_netos,
            'precio_venta_total' => (float) $this->precio_venta_total,
            'margen_ganancia' => (float) $this->margen_ganancia,
            'ganancia_potencial' => (float) $this->ganancia_potencial,
            
            // Timestamps
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            
            // Links usando las rutas que SÍ tienes
            'links' => [
                'self' => route('productos.show', $this->id), // Esta ruta SÍ existe
                'edit' => route('productos.edit', $this->id), // Esta ruta SÍ existe
                'delete' => route('productos.destroy', $this->id) // Esta ruta SÍ existe
            ]
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Producto obtenido exitosamente'
        ];
    }
}